# AEGIS_LARAVEL_STRUCTURE.md
# Aegis — Complete Laravel 11 Application Structure

**Status:** Structure-only reference. Method signatures, docblocks, and stubs — no implementation. Enums are the exception (small enough to deliver as complete code).
**Stack:** Laravel 11 · PHP 8.2+ · MySQL 8 · Inertia.js · Sanctum · Cashier · Horizon · Reverb
**Source files (read in Step 0):**
- `AEGIS_DATABASE_SCHEMA.md` — 2032 lines · 71 tables · Section C enum registry · Section D migration order
- `AEGIS_USE_CASES_OUTPUT.md` — 2552 lines · UC-PRV ×134 · UC-CS ×78 · UC-SS ×52 · UC-BP ×85 · UC-ADM ×41 · UC-XP ×31
- `AEGIS_VUE_STRUCTURE.md` — 1832 lines · every controller's Inertia return shape matches the Vue page's declared props

**Repo commit at time of generation:** `67749f2` "Aegis Laravel Tree"

**Corrected version** — all gaps from `AEGIS_STRUCTURE_GAP_REPORT.md` applied:
- All 32 phantom UC IDs remapped to real UCs (per the `[REMAP]` mapping in `AEGIS_USE_CASES_ADDITIONS.md`)
- 17 models updated with `SoftDeletes` trait
- 12 JSON column casts added across affected models
- 24 datetime casts added for non-trivial timestamps
- 27 missing `belongsTo` relationships added
- Service-called inverse `hasMany` relationships added on User model (concise; other inverses noted as available via Eloquent auto-resolution)
- 4 new policies added (`SubscriptionPolicy`, `NetworkConnectionPolicy`, `HelpArticlePolicy`, `PackagePolicy`)
- 4 new services added (`AdminPayoutService`, `AdminHelpArticleService`, `TaxDocumentService`, `TeamService`)
- ~35 new FormRequest classes added
- ~120 new routes added covering newly-documented UCs
- `UserRole` model renamed to `UserRoleAssignment` (table stays `user_roles`)

---

## Table of Contents

1. [Enums](#section-1--enums) (25 — full code)
2. [Models](#section-2--models) (69 — full stubs)
3. [Services](#section-3--services) (25 — full method tables)
4. [Events + Listeners](#section-4--events--listeners) ← *resumes after review pause*
5. [Jobs](#section-5--jobs)
6. [Policies](#section-6--policies) (14)
7. [Middleware](#section-7--middleware) (8)
8. [Controllers](#section-8--controllers) (~65)
9. [FormRequests](#section-9--formrequests) (~40)
10. [Routes](#section-10--routes)
11. [Seeders](#section-11--seeders)
12. [Config + Packages](#section-12--config--packages)

---

# Section 1 — Enums

All enums live in `app/Enums/`. PHP 8.1+ backed string enums. Values match `AEGIS_DATABASE_SCHEMA.md` Section C exactly. Each enum exposes a `label()` method for UI display + any helpers needed by Services and Policies.

---

## `app/Enums/UserRole.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Practitioner       = 'practitioner';
    case ContinuitySteward  = 'continuity_steward';
    case SupportSteward     = 'support_steward';
    case BusinessPartner    = 'business_partner';
    case Admin              = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Practitioner      => 'Practitioner',
            self::ContinuitySteward => 'Continuity Steward',
            self::SupportSteward    => 'Support Steward',
            self::BusinessPartner   => 'Business Partner',
            self::Admin             => 'Admin',
        };
    }

    /** Portal slug used in routes/URLs */
    public function portal(): string
    {
        return match ($this) {
            self::Practitioner      => 'provider',
            self::ContinuitySteward => 'cs',
            self::SupportSteward    => 'ss',
            self::BusinessPartner   => 'bp',
            self::Admin             => 'admin',
        };
    }

    public static function fromPortal(string $portal): self
    {
        return match ($portal) {
            'provider' => self::Practitioner,
            'cs'       => self::ContinuitySteward,
            'ss'       => self::SupportSteward,
            'bp'       => self::BusinessPartner,
            'admin'    => self::Admin,
            default    => throw new \ValueError("Unknown portal: $portal"),
        };
    }
}
```

---

## `app/Enums/PlanStatus.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

enum PlanStatus: string
{
    case Draft              = 'draft';
    case PendingReview      = 'pending_review';
    case Active             = 'active';
    case AnnualReviewDue    = 'annual_review_due';
    case Expired            = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft           => 'Draft',
            self::PendingReview   => 'Pending Review',
            self::Active          => 'Active',
            self::AnnualReviewDue => 'Annual Review Due',
            self::Expired         => 'Expired',
        };
    }

    /** True when the plan can trigger incident workflows / vault gates */
    public function isLive(): bool
    {
        return in_array($this, [self::Active, self::AnnualReviewDue], true);
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }
}
```

---

## `app/Enums/IncidentType.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

/** 7 approved incident types — confirmed by Carizma (Apr 7). No freeform. */
enum IncidentType: string
{
    case Death              = 'death';
    case Incapacitation     = 'incapacitation';
    case ExtendedAbsence    = 'extended_absence';
    case Missing            = 'missing';            // opt-in
    case Detainment         = 'detainment';         // opt-in
    case NaturalDisaster    = 'natural_disaster';   // opt-in
    case Geopolitical       = 'geopolitical';       // opt-in

    public function label(): string
    {
        return match ($this) {
            self::Death           => 'Death',
            self::Incapacitation  => 'Incapacitation',
            self::ExtendedAbsence => 'Extended Absence',
            self::Missing         => 'Missing',
            self::Detainment      => 'Detainment',
            self::NaturalDisaster => 'Natural Disaster',
            self::Geopolitical    => 'Geopolitical',
        };
    }

    /** Whether this type is opt-in (off by default in plan_incident_configs) */
    public function isOptIn(): bool
    {
        return in_array($this, [
            self::Missing,
            self::Detainment,
            self::NaturalDisaster,
            self::Geopolitical,
        ], true);
    }

    public static function defaultEnabled(): array
    {
        return [self::Death, self::Incapacitation, self::ExtendedAbsence];
    }
}
```

---

## `app/Enums/IncidentStatus.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

enum IncidentStatus: string
{
    case Reported  = 'reported';
    case Verified  = 'verified';
    case Active    = 'active';
    case Closed    = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Reported => 'Reported',
            self::Verified => 'Verified',
            self::Active   => 'Active',
            self::Closed   => 'Closed',
        };
    }

    /** True when vault unseal is permitted */
    public function unsealsVault(): bool
    {
        return $this === self::Active;
    }
}
```

---

## `app/Enums/StewardType.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

enum StewardType: string
{
    case ContinuitySteward = 'continuity_steward';
    case SupportSteward    = 'support_steward';

    public function label(): string
    {
        return match ($this) {
            self::ContinuitySteward => 'Continuity Steward',
            self::SupportSteward    => 'Support Steward',
        };
    }
}
```

---

## `app/Enums/StewardRole.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

enum StewardRole: string
{
    case Primary   = 'primary';
    case Alternate = 'alternate';
    case Support   = 'support';

    public function label(): string
    {
        return match ($this) {
            self::Primary   => 'Primary',
            self::Alternate => 'Alternate',
            self::Support   => 'Support',
        };
    }
}
```

---

## `app/Enums/StewardStatus.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

enum StewardStatus: string
{
    case Invited          = 'invited';
    case Active           = 'active';
    case Declined         = 'declined';
    case RequestIncoming  = 'request_incoming';
    case Archived         = 'archived';
    case Pending          = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Invited         => 'Invited',
            self::Active          => 'Active',
            self::Declined        => 'Declined',
            self::RequestIncoming => 'Request Incoming',
            self::Archived        => 'Archived',
            self::Pending         => 'Pending',
        };
    }

    public function isLive(): bool
    {
        return $this === self::Active;
    }
}
```

---

## `app/Enums/TaskStatus.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

enum TaskStatus: string
{
    case Pending     = 'pending';
    case InProgress  = 'in_progress';
    case Complete    = 'complete';
    case Exception   = 'exception';

    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'Pending',
            self::InProgress => 'In Progress',
            self::Complete   => 'Complete',
            self::Exception  => 'Exception',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::InProgress], true);
    }
}
```

---

## `app/Enums/VaultZone.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

enum VaultZone: string
{
    case Credentials  = 'credentials';
    case Roster       = 'roster';
    case Documents    = 'documents';
    case Instructions = 'instructions';

    public function label(): string
    {
        return match ($this) {
            self::Credentials  => 'Secure Credentials',
            self::Roster       => 'Client Roster',
            self::Documents    => 'Documents',
            self::Instructions => 'Instructions',
        };
    }

    /** AES-256-GCM envelope required (stopgap until Keeper integration) */
    public function isEncrypted(): bool
    {
        return $this === self::Credentials;
    }
}
```

---

## `app/Enums/VaultItemStatus.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

enum VaultItemStatus: string
{
    case VaultOnly = 'vault_only';
    case Active    = 'active';
    case Priority  = 'priority';

    public function label(): string
    {
        return match ($this) {
            self::VaultOnly => 'Vault Only',
            self::Active    => 'Active',
            self::Priority  => 'Priority',
        };
    }
}
```

---

## `app/Enums/DocumentStatus.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

enum DocumentStatus: string
{
    case Draft           = 'draft';
    case Countersign     = 'countersign';
    case Active          = 'active';
    case Archived        = 'archived';
    case ReleasePending  = 'release_pending';

    public function label(): string
    {
        return match ($this) {
            self::Draft          => 'Draft',
            self::Countersign    => 'Awaiting Countersignature',
            self::Active         => 'Active',
            self::Archived       => 'Archived',
            self::ReleasePending => 'Release Pending',
        };
    }
}
```

---

## `app/Enums/ReferralStatus.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

enum ReferralStatus: string
{
    case Sent      = 'sent';
    case Accepted  = 'accepted';
    case Declined  = 'declined';
    case Closed    = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Sent      => 'Sent',
            self::Accepted  => 'Accepted',
            self::Declined  => 'Declined',
            self::Closed    => 'Closed',
            self::Cancelled => 'Cancelled',
        };
    }
}
```

---

## `app/Enums/ServiceStatus.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

enum ServiceStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active   => 'Active',
            self::Inactive => 'Inactive',
        };
    }
}
```

---

## `app/Enums/ServiceRequestStatus.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

enum ServiceRequestStatus: string
{
    case New      = 'new';
    case Accepted = 'accepted';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::New      => 'New',
            self::Accepted => 'Accepted',
            self::Declined => 'Declined',
        };
    }
}
```

---

## `app/Enums/BpJobStatus.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

enum BpJobStatus: string
{
    case Draft     = 'draft';
    case Open      = 'open';
    case Paused    = 'paused';
    case Closed    = 'closed';
    case Filled    = 'filled';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Draft',
            self::Open      => 'Open',
            self::Paused    => 'Paused',
            self::Closed    => 'Closed',
            self::Filled    => 'Filled',
            self::Cancelled => 'Cancelled',
        };
    }

    public function acceptsProposals(): bool
    {
        return $this === self::Open;
    }
}
```

---

## `app/Enums/ProposalStatus.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

enum ProposalStatus: string
{
    case Pending     = 'pending';
    case UnderReview = 'under_review';
    case Accepted    = 'accepted';
    case Declined    = 'declined';
    case Withdrawn   = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Pending     => 'Pending',
            self::UnderReview => 'Under Review',
            self::Accepted    => 'Accepted',
            self::Declined    => 'Declined',
            self::Withdrawn   => 'Withdrawn',
        };
    }
}
```

---

## `app/Enums/ContractStatus.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

enum ContractStatus: string
{
    case Draft     = 'draft';
    case Active    = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Draft',
            self::Active    => 'Active',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }
}
```

---

## `app/Enums/MilestoneStatus.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

enum MilestoneStatus: string
{
    case Pending   = 'pending';
    case Submitted = 'submitted';
    case Approved  = 'approved';
    case Rejected  = 'rejected';
    case Paid      = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Pending',
            self::Submitted => 'Submitted',
            self::Approved  => 'Approved',
            self::Rejected  => 'Rejected',
            self::Paid      => 'Paid',
        };
    }
}
```

---

## `app/Enums/InvoiceStatus.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

/** Used by both bp_invoices and cs_invoices */
enum InvoiceStatus: string
{
    case Draft   = 'draft';
    case Sent    = 'sent';
    case Paid    = 'paid';
    case Overdue = 'overdue';
    case Void    = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Draft   => 'Draft',
            self::Sent    => 'Sent',
            self::Paid    => 'Paid',
            self::Overdue => 'Overdue',
            self::Void    => 'Void',
        };
    }

    public function isCollectible(): bool
    {
        return in_array($this, [self::Sent, self::Overdue], true);
    }
}
```

---

## `app/Enums/PayoutStatus.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

/** Used by both bp_payouts and cs_payouts */
enum PayoutStatus: string
{
    case Pending    = 'pending';
    case InTransit  = 'in_transit';
    case Paid       = 'paid';
    case Failed     = 'failed';
    case Cancelled  = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Pending',
            self::InTransit => 'In Transit',
            self::Paid      => 'Paid',
            self::Failed    => 'Failed',
            self::Cancelled => 'Cancelled',
        };
    }
}
```

---

## `app/Enums/ComplaintStatus.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

enum ComplaintStatus: string
{
    case Open       = 'open';
    case InProgress = 'in_progress';
    case Resolved   = 'resolved';
    case Closed     = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open       => 'Open',
            self::InProgress => 'In Progress',
            self::Resolved   => 'Resolved',
            self::Closed     => 'Closed',
        };
    }
}
```

---

## `app/Enums/ComplaintCategory.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

enum ComplaintCategory: string
{
    case SupportTicket = 'support_ticket';
    case Feedback      = 'feedback';
    case Complaint     = 'complaint';

    public function label(): string
    {
        return match ($this) {
            self::SupportTicket => 'Support Ticket',
            self::Feedback      => 'Feedback',
            self::Complaint     => 'Complaint',
        };
    }
}
```

---

## `app/Enums/SubmissionChannel.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

/** The 3 feedback channels referenced in PENDING-ITEMS §B2 */
enum SubmissionChannel: string
{
    case FeedbackButton = 'feedback_button';
    case Questionnaire  = 'contextual_questionnaire';
    case FreeForm       = 'free_form';
    case HelpTicket     = 'help_ticket';

    public function label(): string
    {
        return match ($this) {
            self::FeedbackButton => 'Feedback Button',
            self::Questionnaire  => 'Contextual Questionnaire',
            self::FreeForm       => 'Free-form Submission',
            self::HelpTicket     => 'Help Ticket',
        };
    }
}
```

---

## `app/Enums/ComplaintPriority.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

enum ComplaintPriority: string
{
    case Low    = 'low';
    case Normal = 'normal';
    case High   = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low    => 'Low',
            self::Normal => 'Normal',
            self::High   => 'High',
            self::Urgent => 'Urgent',
        };
    }

    public function slaHours(): int
    {
        return match ($this) {
            self::Urgent => 4,
            self::High   => 24,
            self::Normal => 72,
            self::Low    => 168,
        };
    }
}
```

---

## `app/Enums/ActivitySeverity.php`

```php
<?php
declare(strict_types=1);

namespace App\Enums;

enum ActivitySeverity: string
{
    case Info     = 'info';
    case Warning  = 'warning';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Info     => 'Info',
            self::Warning  => 'Warning',
            self::Critical => 'Critical',
        };
    }

    /** Severity → bell-notification badge color token */
    public function cssVar(): string
    {
        return match ($this) {
            self::Info     => 'var(--text-3)',
            self::Warning  => 'var(--gold-dark)',
            self::Critical => 'var(--red)',
        };
    }
}
```

---

# Section 2 — Models

One model per table from `AEGIS_DATABASE_SCHEMA.md` (69 total). Column names and types are derived from the schema file — never invented. All meta tables (`user_meta`, `plan_meta`, `incident_meta`, `vault_item_meta`, `referral_meta`, `contract_meta`, `complaint_meta`) inherit from a shared `MetaModel` base class.

**Global conventions:**
- All models use `HasUuids` (PK = `CHAR(36)`).
- All models with `deleted_at` use `SoftDeletes`.
- Timestamps map automatically (`created_at`, `updated_at`).
- Enum columns cast to the matching `App\Enums\*` class.
- JSON columns cast to `'array'`.
- `tinyint(1)` columns cast to `'boolean'`.
- `*_cents` columns are integer cents (no decimal cast).

---

## `app/Models/User.php`
**Table:** `users`
**UCs:** UC-PRV-001,002,003,004,010,016,017,019; UC-CS-001,002,003; UC-SS-001; UC-XP-031; UC-ADM-020..029
**Traits:** `HasFactory`, `Notifiable`, `HasApiTokens` (Sanctum), `Billable` (Cashier), `SoftDeletes`, `HasUuids`
**Casts:**
- `role` → `UserRole`
- `tier` → `\App\Enums\UserTier` *(if added; tier is `access|practice`)*
- `cs_account_type` → string (enum at DB level, not promoted to PHP enum)
- `bp_type` → string
- `bp_categories` → array
- `practitioner_public`, `cs_public`, `business_partner_public`, `services_mode`, `maat_addon`, `stripe_connected`, `verified`, `two_factor_enabled` → boolean
- `slug_locked_at`, `signed_at`, `expires_at`, `locked_at`, `deactivated_at`, `last_login_at` → datetime

**Relationships:**
- `hasOne(ContinuityPlan::class, 'practitioner_id')` → `plan()`
- `hasMany(UserMeta::class)` → `meta()`
- `hasMany(UserRole::class)` → `roles()`
- `hasMany(UserSession::class)` → `sessions()`
- `hasOne(UserPreference::class)` → `preference()`
- `hasOne(MfaToken::class)` → `mfa()`
- `hasMany(ActivityEvent::class, 'user_id')` → `activityEvents()`
- `hasMany(PlanSteward::class, 'steward_id')` → `stewardshipsHeld()` *(plans where I am a steward)*
- `hasMany(VaultItem::class)` → `vaultItems()`
- `hasMany(BpJob::class, 'created_by_id')` → `jobsPosted()` *(when role=practitioner)*
- `hasMany(BpProposal::class, 'bp_id')` → `proposalsSubmitted()` *(when role=business_partner)*
- `hasMany(NetworkConnection::class, 'user_id')` → `connections()`
- `hasMany(Referral::class, 'from_user_id')` → `referralsSent()`
- `hasMany(Service::class, 'practitioner_id')` → `services()`
- `hasMany(Complaint::class, 'submitted_by_id')` → `complaintsSubmitted()`
- `belongsTo(User::class, 'linked_provider_id')` → `linkedProvider()` *(Invited CS only)*
- `belongsTo(User::class, 'invited_by_id')` → `invitedBy()`

**Scopes:**
- `scopeActive($q)` → `whereNull('locked_at')->whereNull('deactivated_at')->whereNull('deleted_at')`
- `scopeByRole($q, UserRole $role)` → `where('role', $role)`
- `scopePublic($q)` → `where(fn($q) => $q->where('practitioner_public', 1)->orWhere('cs_public', 1)->orWhere('business_partner_public', 1))`
- `scopeLocked($q)` → `whereNotNull('locked_at')`

**Fillable:** All non-PK, non-timestamp columns from schema.
**Hidden:** `password`, `remember_token`, `mfa_secret` *(if added)*
**Notes:** Primary role resolved via `user_roles.is_default=1` when multi-role; `users.role` is the canonical default fallback. `id` is the seeded slug (e.g. `p_sarah`, `cs_marcus`), not a `Str::uuid()` — `HasUuids` overridden in the User model to allow seeded IDs.

---

## `app/Models/UserMeta.php`
**Table:** `user_meta`
**UCs:** UC-PRV-012,016,019; EMAIL_TEMPLATES §B
**Traits:** `HasUuids`
**Casts:**
- `meta_type` → string (string|int|boolean|json|timestamp)
**Relationships:**
- `belongsTo(User::class)`
**Fillable:** `user_id`, `meta_key`, `meta_value`, `meta_type`
**Notes:** Sparse attribute table. The `notify_*` boolean gate keys (~26 of them) are read by `NotificationService` to decide whether to send each email type. Provides typed accessor: `typed_value` (a.k.a. `cast()`) based on `meta_type`.

**⚠️ Write pitfall — never pass `id` in an `updateOrCreate` match/update on this table.** `updateOrCreate(['meta_key'=>$k], ['id'=>Str::uuid(), ...])` fails silently or throws a duplicate-key error on the UUID PK during the *update* branch. Use an explicit first-or-create instead:
```php
$row = $user->meta()->where('meta_key', $key)->first();
if ($row) {
    $row->update(['meta_value' => $encoded, 'meta_type' => $type]);
} else {
    $user->meta()->create([
        'id'         => 'um_' . Str::lower(Str::random(12)),  // manual UUID for HasUuids
        'meta_key'   => $key,
        'meta_value' => $encoded,
        'meta_type'  => $type,
    ]);
}
```

**⚠️ Read pitfall — `meta_type` is cast to a value, so string comparison against a raw pluck breaks.** `pluck('meta_type')` returns the cast form, not the literal `'json'` string. To read JSON meta, use the model's `typed_value` accessor rather than manually checking `meta_type === 'json'` and calling `json_decode`. Iterate the loaded relation and build a `key → typed_value` map.

**⚠️ Stale cache after write.** `loadMissing('meta')` keeps a cached relation; after `setMeta()` writes, a read-after-write in the same request returns stale data. Use `$user->load('meta')` (force refresh) in any read that follows a write in the same request cycle.

---

## `app/Models/UserRoleAssignment.php` *(model)*
**Table:** `user_roles`
**UCs:** UC-XP-031; UC-ADM-026
**Traits:** `HasUuids`
**Casts:**
- `role` → `\App\Enums\UserRole`
- `is_default` → boolean
- `enabled_at` → datetime
**Relationships:**
- `belongsTo(User::class)`
**Notes:** Renamed from `UserRole` to avoid collision with the `App\Enums\UserRole` enum. Table name remains `user_roles`.

---

## `app/Models/UserSession.php`
**Table:** `user_sessions`
**UCs:** UC-PRV-002; EMAIL T7
**Traits:** `HasUuids`
**Casts:**
- `last_seen_at`, `revoked_at` → datetime
**Relationships:**
- `belongsTo(User::class)`
**Scopes:**
- `scopeActive($q)` → `whereNull('revoked_at')`
**Fillable:** `user_id`, `session_token`, `ip_address`, `user_agent`, `device_label`, `last_seen_at`, `revoked_at`

---

## `app/Models/UserPreference.php`
**Table:** `user_preferences`
**UCs:** UC-PRV-019
**Traits:** `HasUuids`
**Casts:**
- `compact` → boolean
- `font_size` → integer
**Relationships:**
- `belongsTo(User::class)`
**Fillable:** `user_id`, `theme`, `font_size`, `compact`, `language`, `timezone`, `date_format`, `time_format`, `currency`

---

## `app/Models/PasswordResetToken.php`
**Table:** `password_reset_tokens`
**UCs:** UC-PRV-005; UC-ADM-025
**Traits:** `HasUuids`
**Casts:** `expires_at`, `used_at` → datetime
**Relationships:** `belongsTo(User::class)`
**Scopes:** `scopeActive($q)` → `whereNull('used_at')->where('expires_at', '>', now())`

---

## `app/Models/MfaToken.php`
**Table:** `mfa_tokens`
**UCs:** UC-PRV-002,214; EMAIL T5/T6
**Traits:** `HasUuids`
**Casts:**
- `recovery_codes` → array
- `confirmed_at`, `disabled_at` → datetime
- `secret` → encrypted *(Laravel `encrypted` cast)*
**Relationships:** `belongsTo(User::class)`
**Hidden:** `secret`, `recovery_codes`

---

## `app/Models/ContinuityPlan.php`
**Table:** `continuity_plans`
**UCs:** UC-PRV-030,035,036,038,039,040; UC-XP-002,003,008,014; UC-ADM-021
**Traits:** `HasFactory`, `SoftDeletes`, `HasUuids`
**Casts:**
- `status` → `PlanStatus`
- `plan_version` → integer
- `signed_at`, `expires_at`, `annual_review_date`, `last_review_at`, `vault_attested_at` → datetime
**Relationships:**
- `belongsTo(User::class, 'practitioner_id')` → `practitioner()`
- `hasMany(PlanMeta::class, 'plan_id')` → `meta()`
- `hasMany(PlanSteward::class, 'plan_id')` → `stewards()`
- `hasMany(PlanTask::class, 'plan_id')` → `tasks()`
- `hasMany(PlanIncidentConfig::class, 'plan_id')` → `incidentConfigs()`
- `hasMany(CriticalIncident::class, 'plan_id')` → `incidents()`
- `hasMany(ContinuityDocument::class, 'plan_id')` → `documents()`
- `hasOne(CriticalIncident::class, 'plan_id')->where('status', IncidentStatus::Active)` → `activeIncident()`

**Scopes:**
- `scopeActive($q)` → `where('status', PlanStatus::Active)`
- `scopeReviewDueSoon($q, int $days = 30)` → `where('annual_review_date', '<=', now()->addDays($days))`

**Fillable:** all editable columns from schema.
**Notes:** `vault_attested_at` is the gate for `PlanSeed.attested`. `last_review_at` and `annual_review_notes` were promoted from `plan_meta` to first-class columns in schema v13.

---

## `app/Models/PlanMeta.php`
**Table:** `plan_meta`
**UCs:** UC-PRV-030,035
**Traits:** `HasUuids`
**Relationships:** `belongsTo(ContinuityPlan::class, 'plan_id')`
**Notes:** Sparse plan attributes (template choice, version-change history rows).

---

## `app/Models/PlanSteward.php`
**Table:** `plan_stewards`
**UCs:** UC-PRV-050..057; UC-CS-010..015; UC-SS-001,002
**Traits:** `HasUuids`, `SoftDeletes`
**Casts:**
- `responsibilities` → array
- `permissions` → array
- `deleted_at` → datetime
- `steward_type` → `StewardType`
- `role` → `StewardRole`
- `status` → `StewardStatus`
- `vault_access` → string *(none|metadata|scoped|full)*
- `invited_at`, `countersigned_at` → datetime
- `signed_at` → datetime
- `review_due_at` → datetime
- `request_sent_at` → datetime
- `expires_at` → datetime
- `declined_at` → datetime
- `ss_acknowledged_at` → datetime
**Relationships:**
- `belongsTo(ContinuityPlan::class, 'plan_id')`
- `belongsTo(User::class, 'steward_id')` → `steward()`
- `belongsTo(User::class, 'invited_by_id')` → `invitedBy()`
**Scopes:**
- `scopeActive($q)` → `where('status', StewardStatus::Active)`
- `scopeOfType($q, StewardType $t)` → `where('steward_type', $t)`

---

## `app/Models/PlanTask.php`
**Table:** `plan_tasks`
**UCs:** UC-PRV-033,034; UC-CS-020..025; UC-SS-020..023
**Traits:** `HasUuids`, `SoftDeletes`
**Casts:**
- `assigned_to` → `StewardType`
- `status` → `TaskStatus`
- `due_offset_days` → integer
- `completed_at` → datetime
**Relationships:**
- `belongsTo(ContinuityPlan::class, 'plan_id')`
- `belongsTo(User::class, 'completed_by_id')` → `completedBy()`
**Scopes:**
- `scopeOpen($q)` → `whereIn('status', [TaskStatus::Pending, TaskStatus::InProgress])`
- `scopeForSteward($q, StewardType $t)` → `where('assigned_to', $t)`

---

## `app/Models/PlanIncidentConfig.php`
**Table:** `plan_incident_configs`
**UCs:** UC-PRV-031,032
**Traits:** `HasUuids`
**Casts:**
- `authorized_cs_ids` → array
- `authorized_ss_ids` → array
- `docs_required` → array
- `incident_type` → `IncidentType`
- `enabled` → boolean
- `required_documentation` → array
**Relationships:** `belongsTo(ContinuityPlan::class, 'plan_id')`

---

## `app/Models/CriticalIncident.php`
**Table:** `critical_incidents`
**UCs:** UC-CS-040,041,042,050; UC-SS-040,041; UC-XP-008,009
**Traits:** `HasFactory`, `HasUuids`, `SoftDeletes`
**Casts:**
- `deleted_at` → datetime
- `incident_type` → `IncidentType`
- `status` → `IncidentStatus`
- `severity` → `ActivitySeverity`
- `reported_at`, `verified_at`, `activated_at`, `closed_at` → datetime
**Relationships:**
- `belongsTo(ContinuityPlan::class, 'plan_id')`
- `belongsTo(User::class, 'reported_by_id')` → `reportedBy()`
- `belongsTo(User::class, 'practitioner_id')` → `practitioner()`
- `belongsTo(User::class, 'verified_by_id')` → `verifiedBy()`
- `hasMany(IncidentMeta::class, 'incident_id')` → `meta()`
- `hasMany(IncidentTask::class, 'incident_id')` → `tasks()`
- `hasMany(IncidentUpdate::class, 'incident_id')` → `updates()`
**Scopes:**
- `scopeActive($q)` → `where('status', IncidentStatus::Active)`
- `scopeOpen($q)` → `whereIn('status', [IncidentStatus::Reported, IncidentStatus::Verified, IncidentStatus::Active])`

---

## `app/Models/IncidentMeta.php`
**Table:** `incident_meta`
**Traits:** `HasUuids`
**Relationships:** `belongsTo(CriticalIncident::class, 'incident_id')`

---

## `app/Models/IncidentTask.php`
**Table:** `incident_tasks`
**UCs:** UC-CS-050; UC-SS-040
**Traits:** `HasUuids`
**Casts:**
- `assigned_role` → `StewardType`
- `status` → `TaskStatus`
- `due_at`, `completed_at` → datetime
**Relationships:**
- `belongsTo(CriticalIncident::class, 'incident_id')`
- `belongsTo(User::class, 'completed_by_id')` → `completedBy()`

---

## `app/Models/IncidentUpdate.php`
**Table:** `incident_updates`
**Traits:** `HasUuids`
**Casts:** `update_type` → string *(reported|verified|activated|vault_unsealed|ss_notified|task_added|escalated|closed)*
**Relationships:**
- `belongsTo(CriticalIncident::class, 'incident_id')`
- `belongsTo(User::class, 'actor_id')` → `actor()`

---

## `app/Models/VaultItem.php`
**Table:** `vault_items`
**UCs:** UC-PRV-070,071; UC-CS-061,052
**Traits:** `HasFactory`, `HasUuids`, `SoftDeletes`
**Casts:**
- `access_grant` → array
- `zone` → `VaultZone`
- `status` → `VaultItemStatus`
- `encrypted_payload` → encrypted *(AES-256-GCM envelope; Laravel `encrypted` cast)*
- `client_priority` → integer
**Relationships:**
- `belongsTo(User::class, 'practitioner_id')` → `practitioner()`
- `hasMany(VaultItemMeta::class, 'item_id')` → `meta()`
- `hasMany(VaultAccessLog::class, 'item_id')` → `accessLog()`
**Scopes:**
- `scopeInZone($q, VaultZone $z)` → `where('zone', $z)`
- `scopeUnsealed($q)` → `whereHas('practitioner.plan.activeIncident')`
**Notes:** `encrypted_payload` carries the AES-256-GCM envelope `[version_byte][iv][ciphertext][auth_tag]`. Plaintext field names + categories are stored unencrypted (UI metadata); credential values live inside the envelope.

---

## `app/Models/VaultItemMeta.php`
**Table:** `vault_item_meta`
**Traits:** `HasUuids`
**Relationships:** `belongsTo(VaultItem::class, 'item_id')`

---

## `app/Models/VaultAccessLog.php`
**Table:** `vault_access_log`
**UCs:** UC-CS-062; UC-XP-009
**Traits:** `HasUuids`
**Casts:**
- `access_type` → string *(reveal|download|export|share|view)*
- `accessed_at` → datetime
**Relationships:**
- `belongsTo(VaultItem::class, 'item_id')`
- `belongsTo(User::class, 'actor_id')` → `actor()`
- `belongsTo(User::class, 'practitioner_id')` → `practitioner()`
- `belongsTo(ContinuityDocument::class, 'amends_document_id')` → `amends()`
- `belongsTo(User::class, 'holder_steward_id')` → `holderSteward()`
**Notes:** Append-only log. No update method exposed.

---

## `app/Models/ContinuityDocument.php`
**Table:** `continuity_documents`
**UCs:** UC-PRV-037; UC-CS-070; UC-SS-070
**Traits:** `HasUuids`, `SoftDeletes`
**Casts:**
- `status` → `DocumentStatus`
- `signed_at`, `archived_at` → datetime
- `issued_at` → datetime
- `expires_at` → datetime
**Relationships:**
- `belongsTo(ContinuityPlan::class, 'plan_id')`
- `belongsTo(User::class, 'created_by_id')`
- `belongsTo(self::class, 'parent_document_id')` → `parent()` *(self-FK for versioning)*
- `hasMany(DocumentSignature::class, 'document_id')` → `signatures()`

---

## `app/Models/DocumentSignature.php`
**Table:** `document_signatures`
**Traits:** `HasUuids`
**Casts:**
- `signer_role` → string *(practitioner|continuity_steward)*
- `signed_at` → datetime
**Relationships:**
- `belongsTo(ContinuityDocument::class, 'document_id')`
- `belongsTo(User::class, 'signer_id')` → `signer()`
- `belongsTo(User::class, 'recipient_id')` → `recipient()`
- `belongsTo(User::class, 'requester_id')` → `requester()`

---

## `app/Models/NetworkConnection.php`
**Table:** `network_connections`
**UCs:** UC-PRV-100..107
**Traits:** `HasUuids`, `SoftDeletes`
**Casts:**
- `deleted_at` → datetime
- `connection_type` → string *(practitioner|business_partner)*
- `status` → string *(active|archived)*
- `connected_at` → datetime
**Casts:**
- `responded_at` → datetime

**Relationships:**
- `belongsTo(User::class, 'user_id')` → `owner()`
- `belongsTo(User::class, 'connected_user_id')` → `target()`
- `belongsTo(User::class, 'recipient_id')` → `recipient()`
- `belongsTo(User::class, 'sender_id')` → `sender()`
**Scopes:**
- `scopeActive($q)` → `where('status', 'active')`

---

## `app/Models/NetworkRequest.php`
**Table:** `network_requests`
**Traits:** `HasUuids`
**Casts:**
- `status` → string *(pending|accepted|declined|cancelled)*
- `responded_at` → datetime
**Relationships:**
- `belongsTo(User::class, 'from_user_id')` → `from()`
- `belongsTo(User::class, 'to_user_id')` → `to()`

---

## `app/Models/ShadowConnection.php`
**Table:** `shadow_connections`
**Traits:** `HasUuids`, `SoftDeletes`
**Relationships:** `belongsTo(User::class, 'owner_id')`
**Notes:** Off-platform contact placeholders; no FK to a target user.

---

## `app/Models/Referral.php`
**Table:** `referrals`
**UCs:** UC-PRV-120..127
**Traits:** `HasUuids`, `SoftDeletes`
**Casts:**
- `status` → `ReferralStatus`
- `sent_at`, `accepted_at`, `declined_at`, `closed_at` → datetime
**Relationships:**
- `belongsTo(User::class, 'from_user_id')` → `from()`
- `belongsTo(User::class, 'to_user_id')` → `to()`
- `belongsTo(User::class, 'recipient_id')` → `recipient()`
- `belongsTo(User::class, 'sender_id')` → `sender()`
- `hasMany(ReferralMeta::class, 'referral_id')` → `meta()`

---

## `app/Models/ReferralMeta.php`
**Table:** `referral_meta`
**Traits:** `HasUuids`
**Relationships:** `belongsTo(Referral::class, 'referral_id')`

---

## `app/Models/MessageThread.php`
**Table:** `message_threads`
**UCs:** UC-PRV-160..172
**Traits:** `HasUuids`, `SoftDeletes`
**Casts:**
- `last_message_at` → datetime
- `participant_ids` → array *(if denormalised; otherwise via pivot)*
**Relationships:**
- `belongsTo(User::class, 'created_by_id')`
- `hasMany(Message::class, 'thread_id')` → `messages()`
- `belongsTo(User::class, 'scoped_provider_id')` → `scopedProvider()`

---

## `app/Models/Message.php`
**Table:** `messages`
**Traits:** `HasUuids`, `SoftDeletes`
**Casts:**
- `reactions` → array
- `attachments` → array
- `read_at`, `delivered_at` → datetime
**Relationships:**
- `belongsTo(MessageThread::class, 'thread_id')`
- `belongsTo(User::class, 'from_user_id')` → `sender()`

---

## `app/Models/ActivityEvent.php`
**Table:** `activity_events`
**UCs:** UC-XP-001..010 (cross-portal fanout); every UC with cross-portal impact
**Traits:** `HasUuids`
**Casts:**
- `portal` → `UserRole` *(via fromPortal accessor)*
- `event_type` → string *(message|task|document|incident|vault|compliance|attestation|payment|account|system|referral|news|event|practitioner_unresponsive_flagged)*
- `severity` → `ActivitySeverity`
- `payload` → array
- `read_at` → datetime
**Relationships:**
- `belongsTo(User::class, 'user_id')` → `recipient()`
- `belongsTo(User::class, 'actor_id')` → `actor()`
- `belongsTo(ServiceRequest::class, 'service_request_id')` → `request()`
- `belongsTo(User::class, 'practitioner_id')` → `practitioner()`
**Scopes:**
- `scopeUnread($q)` → `whereNull('read_at')`
- `scopeForPortal($q, string $portal)` → `where('portal', $portal)`
- `scopeOfModule($q, string $module)` → `where('event_type', $module)`
**Notes:** The fan-out target — every cross-portal write helper writes one row per recipient feed via `ActivityService::log()`.

---

## `app/Models/Service.php`
**Table:** `services`
**UCs:** UC-PRV-180..187
**Traits:** `HasUuids`, `SoftDeletes`
**Casts:**
- `price_type` → string *(fixed|hourly|session|inquiry)*
- `status` → `ServiceStatus`
- `price_cents` → integer
- `tags` → array
**Relationships:**
- `belongsTo(User::class, 'practitioner_id')` → `practitioner()`
- `hasMany(ServiceRequest::class)` → `requests()`
- `hasMany(ServiceSession::class)` → `sessions()`

---

## `app/Models/ServiceRequest.php`
**Table:** `service_requests`
**Traits:** `HasUuids`, `SoftDeletes`
**Casts:**
- `deleted_at` → datetime
- `status` → `ServiceRequestStatus`
- `requested_at` → datetime
**Relationships:**
- `belongsTo(Service::class)`
- `belongsTo(User::class, 'requester_id')` → `requester()`
- `belongsTo(User::class, 'practitioner_id')` → `practitioner()`

---

## `app/Models/ServiceSession.php`
**Table:** `service_sessions`
**Traits:** `HasUuids`, `SoftDeletes`
**Casts:**
- `deleted_at` → datetime
- `status` → string *(scheduled|completed|cancelled|no_show)*
- `scheduled_at`, `completed_at` → datetime
**Relationships:**
- `belongsTo(ServiceRequest::class, 'request_id')`
- `belongsTo(Service::class)`
- `belongsTo(User::class, 'client_id')` → `client()`

---

## `app/Models/CeuEntry.php`
**Table:** `ceu_entries`
**UCs:** UC-PRV-200..205
**Traits:** `HasUuids`, `SoftDeletes`
**Casts:**
- `deleted_at` → datetime
- `hours` → decimal:2
- `completed_at` → datetime
**Relationships:** `belongsTo(User::class)`

---

## `app/Models/BpJob.php`
**Table:** `bp_jobs`
**UCs:** UC-PRV-150..155; UC-BP-020..029
**Traits:** `HasUuids`, `SoftDeletes`
**Casts:**
- `budget_type` → string *(fixed|hourly|retainer)*
- `status` → `BpJobStatus`
- `budget_min_cents`, `budget_max_cents` → integer
- `tags`, `requirements` → array
- `posted_at`, `closes_at` → datetime
- `responded_at` → datetime
**Relationships:**
- `belongsTo(User::class, 'created_by_id')` → `practitioner()`
- `hasMany(BpProposal::class, 'job_id')` → `proposals()`
- `hasMany(BpSavedJob::class, 'job_id')` → `savedBy()`

---

## `app/Models/BpProposal.php`
**Table:** `bp_proposals`
**UCs:** UC-BP-030..039
**Traits:** `HasUuids`, `SoftDeletes`
**Casts:**
- `deleted_at` → datetime
- `proposed_rate_type` → string *(fixed|hourly|retainer)*
- `status` → `ProposalStatus`
- `proposed_rate_cents` → integer
- `attachments` → array
- `submitted_at`, `withdrawn_at` → datetime
- `cancelled_at` → datetime
- `completed_at` → datetime
**Relationships:**
- `belongsTo(BpJob::class, 'job_id')`
- `belongsTo(User::class, 'bp_id')` → `bp()`
- `belongsTo(User::class, 'assigned_member_id')` → `assignedMember()`

---

## `app/Models/BpContract.php`
**Table:** `bp_contracts`
**UCs:** UC-BP-040..048
**Traits:** `HasUuids`, `SoftDeletes`
**Casts:**
- `status` → `ContractStatus`
- `total_value_cents` → integer
- `started_at`, `ended_at`, `signed_at` → datetime
**Relationships:**
- `belongsTo(BpJob::class, 'job_id')`
- `belongsTo(BpProposal::class, 'proposal_id')`
- `belongsTo(User::class, 'practitioner_id')` → `practitioner()`
- `belongsTo(User::class, 'bp_id')` → `bp()`
- `hasMany(ContractMeta::class, 'contract_id')` → `meta()`
- `hasMany(BpMilestone::class, 'contract_id')` → `milestones()`
- `hasMany(BpInvoice::class, 'contract_id')` → `invoices()`

---

## `app/Models/ContractMeta.php`
**Table:** `contract_meta`
**Traits:** `HasUuids`
**Relationships:** `belongsTo(BpContract::class, 'contract_id')`

---

## `app/Models/BpMilestone.php`
**Table:** `bp_milestones`
**UCs:** UC-BP-050..057
**Traits:** `HasUuids`, `SoftDeletes`
**Casts:**
- `deleted_at` → datetime
- `status` → `MilestoneStatus`
- `amount_cents` → integer
- `due_at`, `submitted_at`, `approved_at`, `paid_at` → datetime
- `issued_at` → datetime
- `voided_at` → datetime
**Relationships:**
- `belongsTo(BpContract::class, 'contract_id')`
- `belongsTo(User::class, 'assigned_to_id')` → `assignee()` *(agency team-member assignment)*

---

## `app/Models/BpInvoice.php`
**Table:** `bp_invoices`
**UCs:** UC-BP-060..069
**Traits:** `HasUuids`, `SoftDeletes`
**Casts:**
- `status` → `InvoiceStatus`
- `total_cents`, `tax_cents`, `subtotal_cents` → integer
- `sent_at`, `due_at`, `paid_at` → datetime
**Relationships:**
- `belongsTo(BpContract::class, 'contract_id')`
- `belongsTo(User::class, 'bp_id')` → `bp()`
- `belongsTo(User::class, 'practitioner_id')` → `practitioner()`
- `hasMany(BpInvoiceLineItem::class, 'invoice_id')` → `lineItems()`
- `hasMany(BpInvoicePayment::class, 'invoice_id')` → `payments()`

---

## `app/Models/BpInvoiceLineItem.php`
**Table:** `bp_invoice_line_items`
**Traits:** `HasUuids`
**Casts:**
- `unit_price_cents`, `total_cents` → integer
- `quantity` → decimal:2
- `scheduled_at` → datetime
- `paid_at` → datetime
**Relationships:** `belongsTo(BpInvoice::class, 'invoice_id')`

---

## `app/Models/BpInvoicePayment.php`
**Table:** `bp_invoice_payments`
**Traits:** `HasUuids`
**Casts:**
- `method` → string *(stripe|manual|ach|card)*
- `status` → string *(pending|succeeded|failed|refunded)*
- `amount_cents` → integer
- `paid_at` → datetime
**Relationships:**
- `belongsTo(BpInvoice::class, 'invoice_id')`
- `belongsTo(User::class, 'payer_id')` → `payer()`

---

## `app/Models/BpPayout.php`
**Table:** `bp_payouts`
**UCs:** UC-BP-070..074
**Traits:** `HasUuids`
**Casts:**
- `status` → `PayoutStatus`
- `amount_cents` → integer
- `arrived_at` → datetime
**Relationships:** `belongsTo(User::class, 'bp_id')`

---

## `app/Models/BpTaxDocument.php`
**Table:** `bp_tax_documents`
**Traits:** `HasUuids`, `SoftDeletes`
**Casts:**
- `deleted_at` → datetime
- `doc_type` → string *(w9|1099|ein_doc|other)*
- `status` → string *(available|pending|verified)*
- `responded_at` → datetime
- `tax_year` → integer
**Relationships:** `belongsTo(User::class, 'bp_id')`

---

## `app/Models/BpTeamMember.php`
**Table:** `bp_team_members`
**UCs:** UC-BP-080..088
**Traits:** `HasUuids`, `SoftDeletes`
**Casts:**
- `deleted_at` → datetime
- `permission_role` → string *(admin|manager|specialist|viewer)*
- `status` → string *(active|idle|inactive)*
- `joined_at` → datetime
**Relationships:**
- `belongsTo(User::class, 'agency_id')` → `agency()`
- `belongsTo(User::class, 'member_id')` → `member()`
- `belongsTo(PractitionerPaymentMethod::class, 'payment_method_id')` → `paymentMethod()`

---

## `app/Models/BpTeamInvitation.php`
**Table:** `bp_team_invitations`
**Traits:** `HasUuids`
**Casts:**
- `permission_role` → string
- `scheduled_at` → datetime
- `paid_at` → datetime
- `status` → string *(pending|accepted|declined|expired)*
- `expires_at`, `accepted_at` → datetime
**Relationships:** `belongsTo(User::class, 'agency_id')` → `agency()`

---

## `app/Models/BpSavedJob.php`
**Table:** `bp_saved_jobs`
**Traits:** `HasUuids`, `SoftDeletes`
**Casts:**
- `deleted_at` → datetime
- `paid_at` → datetime

**Relationships:**
- `belongsTo(User::class, 'bp_id')`
- `belongsTo(BpJob::class, 'job_id')`

---

## `app/Models/CsInvoice.php`
**Table:** `cs_invoices`
**UCs:** UC-CS-080..085
**Traits:** `HasUuids`
**Casts:**
- `status` → `InvoiceStatus`
- `amount_cents` → integer
- `issued_at`, `due_at`, `paid_at` → datetime
**Relationships:**
- `belongsTo(User::class, 'cs_id')` → `cs()`
- `belongsTo(User::class, 'practitioner_id')` → `practitioner()`

---

## `app/Models/CsPayout.php`
**Table:** `cs_payouts`
**Traits:** `HasUuids`
**Casts:**
- `status` → `PayoutStatus`
- `amount_cents` → integer
- `arrived_at` → datetime
**Relationships:** `belongsTo(User::class, 'cs_id')`

---

## `app/Models/PractitionerPaymentMethod.php`
**Table:** `practitioner_payment_methods`
**UCs:** UC-PRV-145..225
**Traits:** `HasUuids`, `SoftDeletes`
**Casts:**
- `is_default` → boolean
- `expires_at` → datetime
**Relationships:** `belongsTo(User::class, 'practitioner_id')`
**Notes:** Stores Stripe `pm_…` reference only; never raw card data.

---

## `app/Models/PractitionerPayment.php`
**Table:** `practitioner_payments`
**Traits:** `HasUuids`
**Casts:**
- `kind` → string *(subscription|maat_addon|cs_fee|bp_invoice|refund)*
- `status` → string *(paid|failed|refunded|partially_refunded|pending)*
- `amount_cents`, `refunded_cents` → integer
- `charged_at`, `refunded_at` → datetime
**Casts:**
- `deleted_at` → datetime

**Relationships:**
- `belongsTo(User::class, 'practitioner_id')`
- `belongsTo(PractitionerPaymentMethod::class, 'method_id')`

---

## `app/Models/SsProviderCheckin.php`
**Table:** `ss_provider_checkins`
**UCs:** UC-SS-030..033
**Traits:** `HasUuids`
**Casts:**
- `status` → string *(ok|concern|unreachable)*
- `checked_at` → datetime
**Relationships:**
- `belongsTo(User::class, 'ss_id')` → `ss()`
- `belongsTo(User::class, 'practitioner_id')` → `practitioner()`

---

## `app/Models/SsProviderNote.php`
**Table:** `ss_provider_notes`
**Traits:** `HasUuids`
**Relationships:**
- `belongsTo(User::class, 'ss_id')`
- `belongsTo(User::class, 'practitioner_id')`

---

## `app/Models/NewsPost.php`
**Table:** `news_posts`
**UCs:** UC-PRV-240..248
**Traits:** `HasUuids`, `SoftDeletes`
**Casts:**
- `post_type` → string *(post|poll|announcement)*
- `poll_options` → array
- `published_at` → datetime
**Casts:**
- `deleted_at` → datetime

**Relationships:**
- `belongsTo(User::class, 'author_id')` → `author()`
- `hasMany(NewsComment::class, 'post_id')` → `comments()`
- `hasMany(NewsReaction::class, 'post_id')` → `reactions()`
- `hasMany(NewsPollVote::class, 'post_id')` → `pollVotes()`
- `belongsTo(User::class, 'submitter_id')` → `submitter()`

---

## `app/Models/NewsEvent.php`
**Table:** `news_events`
**Traits:** `HasUuids`
**Casts:**
- `starts_at`, `ends_at` → datetime
- `escalated_at` → datetime
**Notes:** No FK — events are admin-curated.

---

## `app/Models/NewsComment.php`
**Table:** `news_comments`
**Traits:** `HasUuids`, `SoftDeletes`
**Relationships:**
- `belongsTo(NewsPost::class, 'post_id')`
- `belongsTo(User::class, 'author_id')`

---

## `app/Models/NewsReaction.php`
**Table:** `news_reactions`
**Traits:** `HasUuids`
**Relationships:**
- `belongsTo(NewsPost::class, 'post_id')`
- `belongsTo(User::class, 'user_id')`

---

## `app/Models/NewsPollVote.php`
**Table:** `news_poll_votes`
**Traits:** `HasUuids`
**Relationships:**
- `belongsTo(NewsPost::class, 'post_id')`
- `belongsTo(User::class, 'user_id')`

---

## `app/Models/NewsTrendingTopic.php`
**Table:** `news_trending_topics`
**Traits:** `HasUuids`
**Notes:** Admin-curated, no FK.

---

## `app/Models/NewsLibraryItem.php`
**Table:** `news_library_items`
**Traits:** `HasUuids`
**Casts:** `tags` → array

---

## `app/Models/Complaint.php`
**Table:** `complaints`
**UCs:** UC-ADM-060..069; UC-XP-040 (3 feedback channels)
**Traits:** `HasUuids`, `SoftDeletes`
**Casts:**
- `category` → `ComplaintCategory`
- `status` → `ComplaintStatus`
- `priority` → `ComplaintPriority`
- `submission_channel` → `SubmissionChannel`
- `resolved_at`, `closed_at` → datetime
**Relationships:**
- `belongsTo(User::class, 'submitted_by_id')` → `submitter()`
- `belongsTo(User::class, 'assigned_to_id')` → `assignee()`
- `hasMany(ComplaintMeta::class, 'complaint_id')` → `meta()`
- `hasMany(ComplaintReply::class, 'complaint_id')` → `replies()`

---

## `app/Models/ComplaintMeta.php`
**Table:** `complaint_meta`
**Traits:** `HasUuids`
**Relationships:** `belongsTo(Complaint::class, 'complaint_id')`

---

## `app/Models/ComplaintReply.php`
**Table:** `complaint_replies`
**Traits:** `HasUuids`
**Casts:** `is_internal_note` → boolean
**Relationships:**
- `belongsTo(Complaint::class, 'complaint_id')`
- `belongsTo(User::class, 'author_id')`

---

## `app/Models/HelpArticle.php`
**Table:** `help_articles`
**Traits:** `HasUuids`, `SoftDeletes`
**Casts:**
- `limits` → array
- `feature_flags` → array
- `tags` → array
- `published_at` → datetime

---

## `app/Models/AdminAuditLog.php`
**Table:** `admin_audit_log`
**UCs:** UC-ADM-030..039
**Traits:** `HasUuids`
**Casts:**
- `payload_json` → array
- `metadata` → array
- `performed_at` → datetime
**Casts:**
- `deleted_at` → datetime

**Relationships:**
- `belongsTo(User::class, 'admin_id')` → `admin()`
- `belongsTo(User::class, 'target_user_id')` → `target()`
**Notes:** Append-only. No update method.

---

## `app/Models/PackageOverride.php`
**Table:** `package_overrides`
**UCs:** UC-ADM-040..045
**Traits:** `HasUuids`
**Casts:**
- `scope` → array
- `tier_data` → array
- `effective_at`, `expires_at` → datetime

---

## `app/Models/Role.php`
**Table:** `roles`
**UCs:** UC-ADM-050..054
**Traits:** `HasUuids`
**Relationships:** `hasMany(RolePermission::class, 'role_id')` → `permissions()`

---

## `app/Models/RolePermission.php`
**Table:** `role_permissions`
**Traits:** `HasUuids`, `SoftDeletes`
**Relationships:** `belongsTo(Role::class, 'role_id')`

---

## `app/Models/StripeWebhookEvent.php`
**Table:** `stripe_webhook_events`
**Traits:** `HasUuids`
**Casts:**
- `deleted_at` → datetime
- `payload` → array
- `processed_at` → datetime
**Notes:** Idempotency table — Cashier writes here on every webhook receipt. Composite UQ on `stripe_event_id`.

---

## `app/Models/ProfileEditAuthorization.php`
**Table:** `profile_edit_authorizations`
**UCs:** UC-XP-020 (MAAT white-glove flow)
**Traits:** `HasUuids`
**Casts:**
- `status` → string *(active|revoked)*
- `granted_at`, `revoked_at` → datetime
- `scopes` → array
**Relationships:**
- `belongsTo(User::class, 'practitioner_id')` → `practitioner()`
- `belongsTo(User::class, 'authorized_user_id')` → `authorized()`

---


---

## `app/Models/ActivityEventRead.php` *(new — UC-PRV-092, UC-PRV-093)*
**Table:** `activity_event_reads`
**UCs:** UC-PRV-092, UC-PRV-093 (and per-portal equivalents UC-CS, UC-SS, UC-BP, UC-ADM)
**Traits:** `HasUuids`
**Casts:**
- `read_at` → datetime
- `created_at` → datetime
**Relationships:**
- `belongsTo(User::class)` → `user()`
- `belongsTo(ActivityEvent::class)` → `event()`
**Notes:** Per-user read receipt for activity feed events. Unique on `(user_id, activity_event_id)`. Without this table, "mark as read" / unread count widgets cannot work.

---

## `app/Models/ProviderCheckin.php` *(renamed from `SsProviderCheckin`)*
**Table:** `provider_checkins` (renamed from `ss_provider_checkins` in migration `000072`)
**UCs:** UC-CS-015, UC-CS-016, UC-SS-040, UC-SS-041
**Traits:** `HasUuids`
**Casts:**
- `steward_type` → enum: `cs|ss`
- `created_at`, `updated_at` → datetime
**Relationships:**
- `belongsTo(User::class, 'steward_id')` → `steward()`
- `belongsTo(User::class, 'practitioner_id')` → `practitioner()`
**Notes:** Polymorphic-ish: same row shape used for both CS proactive check-ins and SS provider check-ins. `steward_type` distinguishes them for queries.


# Section 3 — Services

All services live in `app/Services/`. Conventions:

- Services are **stateless** — constructor receives any DI dependencies only.
- Services are **fat** — controllers call one service method and return Inertia. All business logic, validation invariants, and event firing belong here.
- Every method that has cross-portal impact calls `ActivityService::log()` — marked **Yes** in the table.
- Every method that produces an `Event` listed in Section 4 fires it via `event(new XEvent(...))`.
- Method signatures use typed parameters and return types. PHPDoc above complex returns.

The first table includes a "Notes" column documenting policy gate checks, tier enforcement, and transactional boundaries.

---

## `app/Services/AuthService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| register | `array $data` (validated by `RegisterRequest`) | `User` | UC-PRV-001 | No | `UserRegistered` | Creates user + user_meta defaults + user_preferences row + UserRoleAssignment(default=1). Transactional. |
| login | `string $email, string $password, string $ip, string $userAgent` | `User` | UC-PRV-002 | No (separate `account` event from listener) | `UserLoggedIn` | Increments `failed_login_count` on failure; locks after 5 failures (sets `locked_at`). Returns user on success and creates `user_sessions` row. |
| logout | `User $user, string $sessionToken` | `bool` | UC-PRV-002 | No | — | Revokes `user_sessions` row by setting `revoked_at`. |
| revokeSession | `User $user, string $sessionId` | `bool` | UC-PRV-002 | No | — | Revoke a specific device session. Policy: only user can revoke own sessions. |
| requestPasswordReset | `string $email` | `bool` | UC-PRV-005 | No | `PasswordReset` | Creates `password_reset_tokens` row, dispatches `SendEmailJob`. Always returns true (don't leak existence). |
| confirmPasswordReset | `string $token, string $newPassword` | `bool` | UC-PRV-005 | No | — | Validates token expiry, marks `used_at`, updates password. |
| enableMfa | `User $user` | `array{secret: string, qrUrl: string, recoveryCodes: array}` | UC-PRV-214 | No | — | Creates `mfa_tokens` row, generates TOTP secret + 10 recovery codes. |
| confirmMfa | `User $user, string $code` | `bool` | UC-PRV-214 | Yes → self feed | — | Verifies code, sets `confirmed_at` + `users.two_factor_enabled=1`. |
| disableMfa | `User $user, string $password` | `bool` | UC-PRV-214 | Yes → self feed | — | Sets `disabled_at`, clears `two_factor_enabled`. Requires password re-auth. |
| changePassword | `User $user, string $newPassword, string $currentPassword` | `bool` | UC-PRV-171, UC-CS-093, UC-BP-101, UC-SS-074 | No | — | Verifies current. Revokes other sessions. |
| verifyEmail | `string $token` | `bool` | UC-PRV-213, UC-CS-006, UC-BP-005 | Yes → user | `EmailVerified` | Writes `email_verified_at`. |
| closeAccount | `User $user, string $reason` | `bool` | UC-CS-097, UC-BP-103, UC-SS-078 | Yes → admin queue | — | Soft-deletes user; revokes sessions. |
| revokeAllSessions | `User $user` | `int` | UC-CS-094, UC-BP-101, UC-SS-074 | No | — | Returns count revoked. |

---

## `app/Services/PlanService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| createDraft | `User $practitioner` | `ContinuityPlan` | UC-PRV-030 | No | — | Creates plan in `draft` status + default `plan_incident_configs` rows (3 mandatory types enabled, 4 opt-in disabled). Transactional. |
| addTask | `ContinuityPlan $plan, array $data` | `PlanTask` | UC-PRV-033, UC-PRV-034 | Yes → CS or SS feed (per `assigned_to`) | — | Validates plan status is `draft` or `active`. |
| configureIncidents | `ContinuityPlan $plan, array $configs` | `bool` | UC-PRV-031, UC-PRV-032 | No | — | Bulk update `plan_incident_configs`. The 3 mandatory types cannot be disabled. |
| sendForSignature | `ContinuityPlan $plan` | `bool` | UC-PRV-035 | Yes → CS+SS feeds | `PlanVersionUpdated` | Status `draft` → `pending_review`. Validates ≥1 active CS. |
| sign | `ContinuityPlan $plan, string $signatureName, string $signatureTitle, string $ip` | `bool` | UC-PRV-036 | Yes → CS+SS feeds | `PlanSigned` | Status → `active`; sets `signed_at`, `signature_*`, computes `expires_at`, `annual_review_date`. Transactional. |
| attestVault | `ContinuityPlan $plan, string $note` | `bool` | UC-PRV-040; UC-XP-003 | Yes → CS+SS feeds | `VaultAttested` | Sets `vault_attested_at`, `vault_attestation_note`. Requires policy `VaultPolicy::attest`. |
| beginAnnualReview | `ContinuityPlan $plan` | `bool` | UC-PRV-038 | Yes → practitioner self feed | `AnnualReviewDue` | Status → `annual_review_due`. Triggered by scheduled job 30 days before `annual_review_date`. |
| completeAnnualReview | `ContinuityPlan $plan, array $checklist, string $notes` | `bool` | UC-PRV-039 | Yes → CS+SS feeds | `AnnualReviewCompleted` | Validates all 8 checklist items checked. Sets `last_review_at`, advances `annual_review_date` +365d, status → `active`. Increments `plan_version`. |
| markAnnualReviewDue | `ContinuityPlan $plan` | `bool` | UC-XP-014 | Yes → practitioner self feed | `AnnualReviewDue` | Called by `AnnualReviewReminderJob`. |
| expire | `ContinuityPlan $plan` | `bool` | UC-XP-014 | Yes → practitioner + CS + SS | — | Status → `expired` when `expires_at` < now and review not completed. |
| addTaskNote | `PlanTask $task, User $actor, string $note` | `bool` | UC-CS-032, UC-SS-032 | No | — | Appends to `notes` JSON. |
| requestTaskExtension | `PlanTask $task, DateTime $newDue, User $actor, string $reason` | `bool` | UC-CS-033, UC-SS-033 | Yes → practitioner | — | Practitioner approves separately. |
| clearTaskException | `PlanTask $task, User $actor, string $resolutionNote` | `bool` | UC-CS-036 | Yes → practitioner | — | Status → `pending` or `complete`. |
| escalateTaskToCs | `PlanTask $task, User $ss` | `bool` | UC-SS-034 | Yes → all CS on plan | — | Cross-portal task escalation. |

---

## `app/Services/IncidentService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| report | `ContinuityPlan $plan, User $reporter, IncidentType $type, array $details` | `CriticalIncident` | UC-SS-040; UC-CS-040 | Yes → practitioner + CS + SS | `IncidentReported` | Status: `reported`. Creates `incident_updates` row. Auto-generates incident_tasks from `plan_tasks` where `assigned_to` matches steward. Transactional. |
| verify | `CriticalIncident $incident, User $verifier, string $verificationNote` | `bool` | UC-CS-041 | Yes → all parties | `IncidentVerified` | Status: `reported` → `verified`. Only Primary CS or admin can verify. |
| activate | `CriticalIncident $incident, User $actor` | `bool` | UC-CS-042 | Yes → all parties | `IncidentActivated`, `VaultUnsealed` | Status: `verified` → `active`. Vault becomes unsealed. **Triggers ungated incident emails via `SendIncidentAlertsListener`.** |
| addTask | `CriticalIncident $incident, array $data` | `IncidentTask` | UC-CS-050 | Yes → assigned steward feed | — | |
| completeTask | `IncidentTask $task, User $actor, ?string $exceptionNote = null` | `bool` | UC-CS-050 | Yes → practitioner + other steward feed | — | If `$exceptionNote` provided: status → `exception`. |
| escalate | `CriticalIncident $incident, User $actor, string $reason` | `bool` | UC-SS-041 | Yes → admin feed | `IncidentEscalated` | Used by SS when CS is unresponsive. Notifies admin. |
| close | `CriticalIncident $incident, User $actor, string $closeNote` | `bool` | UC-CS-043 | Yes → all parties | `IncidentClosed` | Status → `closed`. Vault re-seals. |
| markStaleIfNeeded | `CriticalIncident $incident` | `bool` | UC-XP-008 | Yes → admin | — | Called by `StaleIncidentAlertJob` if incident is `verified` >24h without activation. |
| reopen | `CriticalIncident $incident, User $actor, string $reason` | `bool` | UC-CS-049 | Yes → all parties | `IncidentReopened` | Status `closed` → `active`. |
| addUpdate | `CriticalIncident $incident, User $actor, string $body` | `IncidentUpdate` | UC-CS-046 | Yes → other parties | — | Writes `incident_updates` row type=note. |
| attachDocument | `CriticalIncident $incident, UploadedFile $file, User $actor` | `bool` | UC-CS-047, UC-SS-043 | Yes → other parties | — | Writes `incident_meta.attachments` JSON. |
| withdraw | `CriticalIncident $incident, User $ss, string $reason` | `bool` | UC-SS-044 | Yes → practitioner + CS | `IncidentWithdrawn` | Only when status `reported`. |
| escalateToAegis | `CriticalIncident $incident, User $cs, string $reason` | `bool` | UC-CS-045 | Yes → admin queue | `IncidentEscalated` | Routes to admin complaint queue. |
| listForAdmin | `array $filters = [], int $page = 1` | `LengthAwarePaginator<CriticalIncident>` | UC-ADM-068 | No | — | Cross-portal admin view of all incidents. Eager-loads practitioner, lead CS. |

---

## `app/Services/VaultService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| getForPractitioner | `User $practitioner` | `Collection<VaultItem>` | UC-PRV-070 | No | — | Returns all vault items for the practitioner. Used for the owner's own view. |
| getForSteward | `User $steward, ContinuityPlan $plan` | `Collection<VaultItem>` | UC-CS-061 | No | — | Returns metadata only unless plan has active incident AND steward has `vault_access ∈ {scoped, full}`. |
| upload | `User $practitioner, VaultZone $zone, array $data, ?UploadedFile $file = null` | `VaultItem` | UC-PRV-070 | Yes → CS feed *(metadata only — not content)* | — | For Credentials zone: encrypts payload with AES-256-GCM. For Documents zone: uploads file to S3, stores signed-URL ref. Validates plan is `active`. |
| reveal | `VaultItem $item, User $actor` | `array` *(plaintext credential)* | UC-CS-061 | Yes → practitioner feed (vault access) | — | Decrypts payload. Writes `vault_access_log` row with `access_type=reveal`. Policy: `VaultPolicy::viewContents`. |
| signedUrl | `VaultItem $item, User $actor, int $ttlMinutes = 15` | `string` | UC-CS-062 | Yes → practitioner feed | — | Generates S3 signed URL. Writes `vault_access_log` `access_type=download`. |
| delete | `VaultItem $item, User $actor` | `bool` | UC-PRV-070 | Yes → CS feed | — | Soft delete. Policy: owner only. |
| setPermissions | `User $practitioner, array $permissions` | `bool` | UC-PRV-070 | No | — | Updates `plan_stewards.vault_access` per steward. |
| sealCheck | `User $practitioner` | `bool` | UC-XP-003 | No | — | Background sweep — re-seals vault if active incident closed. Called by `VaultSealCheckJob`. |
| update | `VaultItem $item, array $data, User $actor` | `VaultItem` | UC-PRV-198 | Yes → stewards (metadata feed) | — | Re-envelopes encrypted payload for Credentials zone. |
| upsertRosterEntry | `User $practitioner, array $data, ?string $itemId = null` | `VaultItem` | UC-PRV-072 | Yes → CS metadata feed | — | Writes `client_priority` (1–5) for Priority Response Roster. |
| dischargeClient | `VaultItem $item, User $actor` | `bool` | UC-PRV-073 | No | — | Sets status `vault_only`; preserves history. |
| exportAuditLog | `User $practitioner, string $format = 'csv'` | `StreamedResponse` | UC-CS-066 | No | — | Streams `vault_access_log` for the practitioner. |

---

## `app/Services/DocumentService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| createFromPlan | `ContinuityPlan $plan` | `ContinuityDocument` | UC-PRV-037 | No | — | Snapshots a signed plan as an immutable document. |
| addSignature | `ContinuityDocument $doc, User $signer, string $signerRole` | `DocumentSignature` | UC-PRV-036, UC-CS-070 | Yes → other party feed | — | |
| archive | `ContinuityDocument $doc, User $actor` | `bool` | UC-PRV-037 | Yes → CS feed | — | Status → `archived`. |
| version | `ContinuityDocument $doc, array $changes` | `ContinuityDocument` | UC-PRV-035 | Yes → CS+SS feeds | — | Creates new document with `parent_document_id` set. |
| signedUrl | `ContinuityDocument $doc, User $actor` | `string` | UC-PRV-037 | No | — | S3 signed URL for the PDF. |
| sendForSignature | `ContinuityDocument $doc, array $signers, User $actor` | `bool` | UC-PRV-190 | Yes → each signer | — | Status → `countersign`. |
| amend | `ContinuityDocument $doc, array $changes, User $actor` | `ContinuityDocument` | UC-PRV-192 | No | — | Creates new draft with `parent_document_id`. |
| renew | `ContinuityDocument $doc, DateTime $newExpiry, User $actor` | `ContinuityDocument` | UC-PRV-193 | Yes → signers | — | Copies content into new version. |
| sendReminder | `ContinuityDocument $doc, User $signer, User $actor` | `bool` | UC-PRV-197 | Yes → signer email | — | Ungated. |
| requestRelease | `ContinuityDocument $doc, User $steward, User $practitioner` | `bool` | UC-PRV-196 | Yes → steward | — | Writes task to steward. |
| declineSignature | `ContinuityDocument $doc, User $signer, string $reason` | `bool` | UC-CS-074 | Yes → practitioner | — | Blocks activation. |
| uploadByStreward | `User $steward, ContinuityPlan $plan, UploadedFile $file` | `ContinuityDocument` | UC-CS-071 | Yes → practitioner | — | Steward as `created_by_id`. |
| uploadLibrary | `User $practitioner, UploadedFile $file, array $meta` | `ContinuityDocument` | UC-PRV-081 | No | — | Not part of plan-document chain. |
| requestFromSteward | `User $practitioner, User $steward, string $description` | `bool` | UC-PRV-082 | Yes → steward task | — | Creates request only. |

---

## `app/Services/StewardService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| inviteExisting | `ContinuityPlan $plan, User $invitee, StewardType $type, StewardRole $role` | `PlanSteward` | UC-PRV-050, UC-PRV-052 | Yes → invitee feed | `StewardDesignated` | Existing Aegis user. Status: `invited`. |
| inviteExternal | `ContinuityPlan $plan, string $email, string $name, StewardType $type, StewardRole $role` | `PlanSteward` | UC-PRV-051 | No | `StewardDesignated` | Creates placeholder user + invite email. Status: `invited`. |
| accept | `PlanSteward $designation, User $steward` | `bool` | UC-CS-010 | Yes → practitioner feed | `StewardAccepted` | Status → `active`. Sets `countersigned_at`. |
| decline | `PlanSteward $designation, User $steward, ?string $reason = null` | `bool` | UC-CS-011 | Yes → practitioner feed | `StewardDeclined` | Status → `declined`. |
| remove | `PlanSteward $designation, User $actor, string $reason` | `bool` | UC-PRV-053, UC-PRV-056 | Yes → steward feed | `StewardRemoved` | Status → `archived`. Validates ≥1 Primary CS remains. |
| activateAlternate | `ContinuityPlan $plan, PlanSteward $alternate, User $actor` | `bool` | UC-PRV-052; UC-CS-012 | Yes → all parties | `AlternateCSActivated` | Demotes primary, promotes alternate. |
| notifyUnresponsive | `User $practitioner, User $cs, User $ss, string $reason` | `ActivityEvent` | UC-SS-042 | Yes → practitioner + admin | — | SS-initiated CS-unresponsive flag. Does NOT change designation. |
| listForPractitioner | `User $practitioner, StewardType $type` | `Collection<PlanSteward>` | UC-PRV-053..057 | No | — | |
| listForSteward | `User $steward, StewardType $type` | `Collection<PlanSteward>` | UC-CS-013, UC-SS-021 | No | — | All plans where this user is a steward. |
| resign | `PlanSteward $designation, string $reason` | `bool` | UC-CS-026 | Yes → practitioner (with alternate suggestion) | `StewardResigned` | Status → `archived`. Suggests alternates from same plan. |
| requestRoleChange | `PlanSteward $designation, string $newRole, User $actor` | `bool` | UC-CS-023 | Yes → practitioner | — | Status → `request_incoming`. |
| copyTasksFromPrimary | `PlanSteward $target` | `int` | UC-PRV-062 | No | — | Duplicates `plan_tasks` from primary to alternate; returns count. |
| recommendBusinessCS | `User $ss, User $practitioner, User $businessCs` | `bool` | UC-SS-018 | Yes → practitioner | — | Writes activity; not a designation. |
| saveProviderNote | `User $ss, User $practitioner, string $note` | `SsProviderNote` | UC-SS-019 | No | — | Private to SS. |
| setPaymentModel | `PlanSteward $designation, string $model, User $actor` | `bool` | UC-PRV-146, UC-CS-088 | Yes → other party | — | Enum: retainer|activation|hourly|pro_bono. |
| pauseStewardship | `User $cs, ?DateTime $until = null` | `bool` | UC-CS-096 | No | — | Writes `user_meta.steward_paused_until`. |
| reAttestAnnual | `PlanSteward $designation` | `bool` | UC-CS-075 | Yes → practitioner | — | Sets `signed_at=now`. |
| resendInvite | `PlanSteward $designation` | `bool` | UC-PRV-206 | Yes → invitee email | — | Dispatches same template as original invite. |
| countersignAnnualReview | `PlanSteward $designation, User $steward` | `bool` | UC-CS-035 | Yes → practitioner | — | Steward side of annual re-attestation. |
| notifyUnresponsive | `User $ss, User $practitioner, User $cs` | `bool` | UC-SS-017 | Yes → practitioner | — | Writes activity tagged `cs_unresponsive`. |
| addCheckin | `User $cs, User $practitioner, string $notes` | `ProviderCheckin` | UC-CS-015 | Yes → practitioner | — | Writes `provider_checkins` with `steward_type=cs`. |

---

## `app/Services/ReferralService.php`
**UCs:** UC-PRV-108, UC-PRV-110, UC-PRV-111, UC-PRV-121, UC-PRV-122, UC-PRV-123, UC-PRV-124, UC-CS-065, UC-CS-111
**Methods:**

| Method | Signature | Returns | UC | Fan-out? | Event | Notes |
|---|---|---|---|---|---|---|
| listForUser | `User $user, string $direction = 'all', array $filters = []` | `Collection<Referral>` | UC-PRV-120 | No | — | direction: all|incoming|outgoing. |
| send | `User $sender, User $recipient, array $payload` | `Referral` | UC-PRV-108, UC-PRV-121, UC-CS-111 | Yes → recipient | — | Status `pending`. Gated by recipient `notify_referral_received`. |
| accept | `Referral $referral, User $recipient` | `bool` | UC-PRV-111, UC-PRV-122 | Yes → sender | — | Status → `accepted`. |
| decline | `Referral $referral, User $recipient, ?string $reason = null` | `bool` | UC-PRV-111, UC-PRV-123 | Yes → sender | — | Status → `declined`. |
| close | `Referral $referral, User $actor` | `bool` | UC-PRV-124 | Yes → other party | — | Status → `closed`. |
| cancel | `Referral $referral, User $actor` | `bool` | UC-PRV-110 | Yes → recipient | — | Sender-side cancel. Status → `cancelled`. |
| sendDuringIncident | `User $cs, User $client, User $targetPractitioner, string $incidentId` | `Referral` | UC-CS-065 | Yes → recipient practitioner | — | Tagged with `incident_id` in `referral_meta`. |

---

## `app/Services/NetworkService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| request | `User $from, User $to, string $note` | `NetworkRequest` | UC-PRV-100 | Yes → recipient feed | — | Status: `pending`. |
| accept | `NetworkRequest $req` | `NetworkConnection` | UC-PRV-101 | Yes → requester feed | — | Creates connection rows for both sides. |
| decline | `NetworkRequest $req` | `bool` | UC-PRV-102 | No | — | |
| archive | `NetworkConnection $conn, User $actor` | `bool` | UC-PRV-103 | No | — | |
| addShadow | `User $owner, array $contactData` | `ShadowConnection` | UC-PRV-105 | No | — | Off-platform contact placeholder. |
| inviteShadowToAegis | `ShadowConnection $shadow` | `bool` | UC-PRV-106 | No | — | Sends Aegis invite email; when accepted, promotes shadow to NetworkConnection. |
| listConnections | `User $user, array $filters = []` | `Collection<NetworkConnection>` | UC-PRV-100..107 | No | — | Filterable by type, status, search. |

---

## `app/Services/MessagingService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| createThread | `User $creator, array $participantIds, ?string $subject = null` | `MessageThread` | UC-PRV-160 | Yes → other participants | — | Validates no duplicate 1:1 thread exists. |
| send | `MessageThread $thread, User $sender, string $body, array $attachments = []` | `Message` | UC-PRV-161 | Yes → other participants | — | Updates `last_message_at`. Fires broadcast event for real-time (Reverb). |
| markRead | `MessageThread $thread, User $reader` | `bool` | UC-PRV-162 | No | — | Bulk sets `read_at` for all unread messages in the thread. |
| listThreads | `User $user, array $filters = []` | `Collection<MessageThread>` | UC-PRV-160..172 | No | — | Filter buckets are role-specific (mirrors `msg_buckets()` in PHP). |
| archive | `MessageThread $thread, User $actor` | `bool` | UC-PRV-170 | No | — | |
| useTemplate | `string $templateKey, UserRole $role` | `string` | UC-PRV-172 | No | — | Returns role-scoped message template body. |
| togglePin | `MessageThread $thread, User $user` | `bool` | UC-PRV-163 | No | — | Per-user pin via `message_threads.pinned_user_ids` JSON. |
| toggleMute | `MessageThread $thread, User $user` | `bool` | UC-PRV-163 | No | — | Per-user mute. |
| markUnread | `MessageThread $thread, User $user` | `bool` | UC-PRV-164 | No | — | Nullifies last-read pointer. |

---

## `app/Services/ActivityService.php`

**The central fan-out writer.** Every cross-portal service method calls `ActivityService::log()`. Listeners use it to translate events into per-recipient feed rows.

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| log | `string $userId, string $portal, string $eventType, string $severity, ?string $actorId, array $payload, ?string $module = null` | `ActivityEvent` | UC-XP-001..010 (all cross-portal UCs) | n/a (this IS the log method) | — | Single canonical write. Returns the created row. |
| logFanout | `array $recipients, string $eventType, string $severity, ?string $actorId, array $payload` | `Collection<ActivityEvent>` | UC-XP-002,003,007 | n/a | — | Bulk version — one row per `[recipient_id, recipient_portal]` tuple. Used by `ActivityFanoutListener`. |
| getRecent | `User $user, int $limit = 5` | `Collection<ActivityEvent>` | UC-PRV-090 (dashboard widget) | No | — | |
| getUnreadCount | `User $user` | `int` | UC-PRV-091 (bell badge) | No | — | |
| getFiltered | `User $user, array $filters, int $page, int $perPage = 20` | `LengthAwarePaginator<ActivityEvent>` | UC-PRV-090..094 | No | — | Filters: `event_type`, `severity`, `module`, date range. Mirrors `aegis_get_activity()` semantics. |
| markRead | `User $user, string $eventId` | `bool` | UC-PRV-092 | No | — | |
| markAllRead | `User $user, ?string $module = null` | `int` | UC-PRV-093 | No | — | Returns count marked. |
| markRead | `User $user, string $eventId` | `bool` | UC-PRV-092 | No | — | Writes `activity_event_reads` row. Idempotent. |
| markAllRead | `User $user, ?string $module = null` | `int` | UC-PRV-093 | No | — | Bulk insert for all unread events; returns count. |

---

## `app/Services/NotificationService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| send | `User $recipient, string $template, array $data, ?string $gateKey = null` | `bool` | UC-XP-011..019 (email delivery) | No | — | Wraps `Mail::to($recipient)->queue(...)`. If `$gateKey` is set, checks `user_meta` `notify_$gateKey` boolean first — skips send if disabled. |
| sendUngated | `User $recipient, string $template, array $data` | `bool` | UC-CS-040, UC-SS-040, UC-PRV-005 (incident, password reset) | No | — | Same as `send()` but ignores notification gate. Used for security-critical sends. |
| isGated | `User $user, string $gateKey` | `bool` | UC-PRV-016 | No | — | Reads `user_meta` value. Defaults to true (opt-out, not opt-in). |
| setGate | `User $user, string $gateKey, bool $enabled` | `bool` | UC-PRV-016 | No | — | Upserts `user_meta`. |
| listGates | `User $user` | `array` | UC-PRV-016 | No | — | Returns all 26 `notify_*` keys with current values for the Settings → Email Preferences panel. |

---

## `app/Services/ServiceService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| create | `User $practitioner, array $data` | `Service` | UC-PRV-180 | No | — | Requires `services_mode=1`. Defaults `status=active`. |
| update | `Service $service, array $data` | `bool` | UC-PRV-181 | No | — | |
| toggleStatus | `Service $service` | `bool` | UC-PRV-182 | No | — | Active ↔ Inactive. |
| delete | `Service $service` | `bool` | UC-PRV-183 | No | — | Soft delete. |
| requestBooking | `Service $service, User $requester, array $data` | `ServiceRequest` | UC-PRV-185 | Yes → practitioner feed | — | Status: `new`. |
| acceptRequest | `ServiceRequest $req, User $actor` | `ServiceSession` | UC-PRV-186 | Yes → requester feed | — | Creates session. Status: `accepted` + session `scheduled`. |
| declineRequest | `ServiceRequest $req, User $actor, ?string $reason = null` | `bool` | UC-PRV-187 | Yes → requester feed | — | |
| completeSession | `ServiceSession $session, User $actor` | `bool` | UC-PRV-188 | Yes → client feed | — | Status → `completed`. |
| togglePublic | `Service $service, bool $public, User $practitioner` | `bool` | UC-PRV-127 | No | — | Updates public profile snapshot. |
| declineRequest | `ServiceRequest $req, User $practitioner, ?string $reason = null` | `bool` | UC-PRV-187 | Yes → inquirer | — | Status → `declined`. |
| completeSession | `ServiceSession $session, User $practitioner` | `bool` | UC-PRV-188 | Yes → client | — | Status → `completed`. Sets `completed_at`. |

---

## `app/Services/CeuService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| add | `User $user, array $data` | `CeuEntry` | UC-PRV-200 | No | — | Validates `hours > 0`. |
| update | `CeuEntry $entry, array $data` | `bool` | UC-PRV-201 | No | — | |
| delete | `CeuEntry $entry` | `bool` | UC-PRV-202 | No | — | |
| totals | `User $user, ?int $year = null` | `array{total_hours: float, by_category: array}` | UC-PRV-203 | No | — | |
| certificateUrl | `CeuEntry $entry` | `string` | UC-PRV-205 | No | — | S3 signed URL. |

---

## `app/Services/BpJobService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| create | `User $practitioner, array $data` | `BpJob` | UC-PRV-150 | No (until publish) | — | Status: `draft`. |
| publish | `BpJob $job` | `bool` | UC-PRV-151 | Yes → BP marketplace (broadcast) | — | Status: `draft` → `open`. Sets `posted_at`. |
| update | `BpJob $job, array $data` | `bool` | UC-PRV-152 | No | — | |
| pause | `BpJob $job` | `bool` | UC-PRV-153 | No | — | |
| close | `BpJob $job` | `bool` | UC-PRV-154 | Yes → applicants (all proposing BPs) | — | Status → `closed`. |
| markFilled | `BpJob $job, BpProposal $accepted` | `bool` | UC-PRV-155 | Yes → all proposing BPs | — | Auto-called by `ProposalService::accept()`. |
| save | `User $bp, BpJob $job` | `BpSavedJob` | UC-BP-022 | No | — | BP saves for later. |
| unsave | `User $bp, BpJob $job` | `bool` | UC-BP-023 | No | — | |
| listOpen | `User $bp, array $filters = []` | `LengthAwarePaginator<BpJob>` | UC-BP-020 | No | — | Marketplace view for BP. |

---

## `app/Services/ProposalService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| submit | `BpJob $job, User $bp, array $data` | `BpProposal` | UC-BP-030 | Yes → practitioner feed | — | Validates job is `open`. Status: `pending`. |
| update | `BpProposal $proposal, array $data` | `bool` | UC-BP-031 | No | — | Only while status `pending` and not yet viewed. |
| withdraw | `BpProposal $proposal, User $actor` | `bool` | UC-BP-032 | Yes → practitioner feed | — | Status → `withdrawn`. |
| markUnderReview | `BpProposal $proposal` | `bool` | UC-PRV-131 | No | — | Practitioner opens — status `pending` → `under_review`. |
| accept | `BpProposal $proposal, User $practitioner` | `BpContract` | UC-PRV-132, UC-BP-038 | Yes → BP feed + other BPs (job filled) | `ProposalAccepted`, `ContractCreated` | Status → `accepted`. Auto-generates contract via `ContractService::generateFromProposal()`. Marks job filled. **Transactional.** |
| decline | `BpProposal $proposal, User $practitioner, ?string $reason = null` | `bool` | UC-PRV-133 | Yes → BP feed | — | Status → `declined`. |

---

## `app/Services/ContractService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| generateFromProposal | `BpProposal $proposal` | `BpContract` | UC-BP-040 | Yes → both parties | `ContractCreated` | Creates `bp_contracts` row + `bp_milestones` rows from proposal scope. Status: `draft`. |
| signByPractitioner | `BpContract $contract, User $practitioner, string $sigName, string $ip` | `bool` | UC-BP-041 | Yes → BP feed | — | Sets `signed_at` partial. |
| signByBp | `BpContract $contract, User $bp, string $sigName, string $ip` | `bool` | UC-BP-042 | Yes → practitioner feed | `ContractSigned` | When both signatures present: status → `active`. |
| cancel | `BpContract $contract, User $actor, string $reason` | `bool` | UC-BP-035 | Yes → other party | — | Status → `cancelled`. |
| complete | `BpContract $contract` | `bool` | UC-BP-047 | Yes → both parties | — | Auto-called when all milestones paid. Status → `completed`. |
| reassign | `BpContract $contract, User $newAssignee, User $actor` | `bool` | UC-BP-048 | Yes → both parties | — | Agency-only. Reassigns to different team member. Writes contract_meta row. |
| pause | `BpContract $contract, User $actor, string $reason` | `bool` | UC-BP-036 | Yes → other party | — | Writes `contract_meta.pause_state`; freezes milestone clock. |
| resume | `BpContract $contract, User $actor` | `bool` | UC-BP-037 | Yes → other party | — | Resumes milestone clock. |
| addMilestone | `BpContract $contract, array $data, User $actor` | `BpMilestone` | UC-BP-039 | Yes → other party | — | Status `pending` until practitioner approves. |
| withdrawMilestone | `BpMilestone $milestone, User $bp` | `bool` | UC-BP-043 | Yes → practitioner | — | Only when status `submitted`. |
| approveMilestone | `BpMilestone $milestone, User $practitioner` | `bool` | UC-PRV-135 | Yes → BP feed | `MilestoneApproved` | Triggers `PayoutService::initiate()`. |
| signByPractitioner | `BpContract $contract, User $practitioner, string $sigName, string $ip` | `bool` | UC-PRV-137 | Yes → BP feed | `ContractSigned` | If both signed → status `active`. |
| complete | `BpContract $contract` | `bool` | UC-BP-047 | Yes → both parties | `ContractCompleted` | Auto-called when all milestones approved/paid. Status → `completed`. |
| reassign | `BpContract $contract, User $newAssignee, User $actor` | `bool` | UC-BP-048 | Yes → both parties | — | Agency-only. Writes `contract_meta` history row. |

---

## `app/Services/InvoiceService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| create | `BpContract $contract, User $bp, array $data` | `BpInvoice` | UC-BP-060 | No (until send) | — | Status: `draft`. |
| addLineItem | `BpInvoice $invoice, array $data` | `BpInvoiceLineItem` | UC-BP-061 | No | — | Recomputes `subtotal_cents`, `total_cents`. |
| send | `BpInvoice $invoice` | `bool` | UC-BP-062 | Yes → practitioner feed | `InvoiceSent` | Status → `sent`. Sets `sent_at`, `due_at` (+30d). |
| approve | `BpInvoice $invoice, User $practitioner` | `BpInvoicePayment` | UC-PRV-160 | Yes → BP feed | `InvoicePaid` | Charges via Stripe Customer pm. Status → `paid`. Triggers `PayoutService::initiate()`. **Transactional.** |
| dispute | `BpInvoice $invoice, User $practitioner, string $reason` | `bool` | UC-PRV-161 | Yes → BP + admin feed | — | Writes complaint of category `complaint`. |
| void | `BpInvoice $invoice, User $actor` | `bool` | UC-BP-055 | Yes → other party | — | Only when status is `draft` or `sent`. |
| markOverdue | `BpInvoice $invoice` | `bool` | UC-XP-015 | Yes → both parties | — | Called by scheduled sweep when `due_at` < now. |
| resend | `BpInvoice $invoice, User $actor` | `bool` | UC-BP-052 | Yes → practitioner | `InvoiceSent` | Re-dispatches email. |
| sendReminder | `BpInvoice|CsInvoice $invoice, User $actor` | `bool` | UC-BP-053, UC-CS-082 | Yes → recipient | — | Allowed only if status ∈ {sent, overdue}. |
| refund | `BpInvoice $invoice, int $amountCents, User $actor, string $reason` | `BpInvoicePayment` | UC-BP-056 | Yes → practitioner + BP | `RefundProcessed` | Stripe refund call. Writes payment row status=refunded. |
| markPaidManually | `BpInvoice|CsInvoice $invoice, User $actor, string $reference` | `bool` | UC-BP-057, UC-CS-083 | Yes → other party | `InvoicePaid` | Status → `paid`. Writes manual payment row. |
| updateDraft | `BpInvoice $invoice, array $data, User $bp` | `BpInvoice` | UC-BP-120 | No | — | Only when status=`draft`. |
| createCsInvoice | `User $cs, User $practitioner, array $lines` | `CsInvoice` | UC-CS-085 | No | — | Status `draft`. No send. |
| sendCsInvoice | `CsInvoice $invoice, User $cs` | `bool` | UC-CS-081 | Yes → practitioner | `InvoiceSent` | Status `draft` → `sent`. |
| voidCsInvoice | `CsInvoice $invoice, User $cs` | `bool` | UC-CS-084 | Yes → practitioner | `InvoiceVoided` | Only when status ∈ {draft, sent}. |
| overrideStatus | `BpInvoice|CsInvoice $invoice, string $status, string $note, User $admin` | `bool` | UC-PRV-208 `[admin only]` | No | — | Admin override. Writes `admin_audit_log`. |

---

## `app/Services/PayoutService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| initiate | `User $recipient, int $amountCents, string $context` | `BpPayout\|CsPayout` | UC-BP-070, UC-CS-080 | Yes → recipient feed | — | Stripe Connect transfer. Status: `pending`. Creates `bp_payouts` or `cs_payouts` per `$recipient->role`. |
| markInTransit | `BpPayout\|CsPayout $payout` | `bool` | UC-XP-016 | No | — | Called by Stripe webhook. |
| markPaid | `BpPayout\|CsPayout $payout` | `bool` | UC-XP-017 | Yes → recipient feed | `PayoutReleased` | Called by Stripe webhook. |
| markFailed | `BpPayout\|CsPayout $payout, string $reason` | `bool` | UC-XP-018 | Yes → recipient + admin | — | Called by Stripe webhook. |
| exportStripeData | `User $bp` | `string` | UC-BP-075 | No | — | Pulls Stripe Connect account data via API. |
| disconnect | `User $bp` | `bool` | UC-BP-076 | Yes → BP feed | — | Clears `users.stripe_account_id`. |
| startConnectOnboarding | `User $user` | `string` | UC-BP-004, UC-CS-086 | No | — | Returns Stripe Connect onboarding URL. |

---

## `app/Services/SubscriptionService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| subscribeAccess | `User $user, string $paymentMethodId` | `bool` | UC-PRV-003 | Yes → self feed | — | Stripe Billing — `access` price. |
| subscribePractice | `User $user, string $paymentMethodId` | `bool` | UC-PRV-003 | Yes → self feed | — | Stripe Billing — `practice` price. |
| upgrade | `User $user, string $newTier` | `bool` | UC-PRV-003 | Yes → self feed | — | Access → Practice (proration). |
| downgrade | `User $user, string $newTier` | `bool` | UC-PRV-004 | Yes → self feed | — | Practice → Access at next period end. |
| addServicesMode | `User $user` | `bool` | UC-PRV-017 | Yes → self feed | — | Adds +$19/mo add-on. Sets `services_mode=1`. |
| removeServicesMode | `User $user` | `bool` | UC-PRV-017 | Yes → self feed | — | |
| addMaatAddon | `User $user` | `bool` | UC-PRV-210 | Yes → self + admin feed | — | Adds +$29/mo MAAT Professional CS Service. Sets `maat_addon=1`. |
| removeMaatAddon | `User $user` | `bool` | UC-PRV-211 | Yes → self + admin feed | — | |
| cancel | `User $user, ?string $reason = null` | `bool` | UC-PRV-145 | Yes → self + admin feed | — | Schedules cancellation at period end. |
| getPricingSnapshot | `void` | `array` | UC-PRV-003,004,017,210 | No | — | Returns current tier pricing from `package_overrides` or fallback config. |
| addPaymentMethod | `User $user, string $stripePmId` | `PractitionerPaymentMethod` | UC-PRV-141 | No | — | Sets default if first. |
| setAutopay | `User $user, bool $enabled` | `bool` | UC-PRV-144 | No | — | Writes `user_meta.autopay_enabled`. |
| upgradeCsToBusiness | `User $cs` | `bool` | UC-CS-114 | Yes → CS feed | — | Sets `cs_account_type=business`. First Stripe charge. |

---

## `app/Services/ProfileService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| updateBasic | `User $user, array $data` | `bool` | UC-PRV-010 | No | — | display_name, phone, location, etc. |
| updateCredentials | `User $user, array $data` | `bool` | UC-PRV-011 | No | — | credentials, license_state, license_number — license_number masked in `user_meta`. |
| updateSpecialties | `User $user, array $specialties` | `bool` | UC-PRV-012 | No | — | Writes to `user_meta` `practitioner_specialties`. |
| updateFees | `User $user, array $data` | `bool` | UC-PRV-015 | No | — | |
| updateAvailability | `User $user, string $status, bool $accepting` | `bool` | UC-PRV-016 | No | — | `availability_status` + `accepting_status` in `user_meta`. |
| lockSlug | `User $user, string $slug` | `bool` | UC-PRV-018 | No | — | Sets `slug` + `slug_locked_at`. Validates uniqueness. |
| updateVisibility | `User $user, array $flags` | `bool` | UC-PRV-019; UC-XP-027..030 | No | — | `practitioner_public`, `cs_public`, `business_partner_public`. |
| grantEditAuthorization | `User $practitioner, User $editor, array $scopes, \DateTimeInterface $expiresAt` | `ProfileEditAuthorization` | UC-XP-020 (MAAT white-glove) | Yes → both parties | — | Status: `active`. |
| revokeEditAuthorization | `ProfileEditAuthorization $auth, User $actor` | `bool` | UC-XP-020 | Yes → both parties | — | Status → `revoked`. |
| computeCompletion | `User $user` | `array{percent: int, incomplete: array}` | UC-PRV-014 | No | — | Returns dashboard ProfileStrip data. |
| updateServicesOffered | `User $user, array $services` | `bool` | UC-PRV-013 | No | — | Writes `user_meta.services_offered` JSON. |
| setIntentSegment | `User $user, string $segment` | `bool` | UC-PRV-215 | No | — | Onboarding intent capture. |
| exportUserData | `User $user` | `string` | UC-PRV-173, UC-CS-095, UC-BP-108, UC-SS-077 | No | — | Queues ZIP generation; emails download link. |
| setRevenueVisibility | `User $user, bool $public` | `bool` | UC-BP-017 | No | — | BP revenue privacy toggle. |
| publicSsProfile | `User $ss, ?User $viewer = null` | `?array` | UC-SS-003 | No | — | Relationship-gated. Returns null unless viewer is admin, an associated practitioner, or has an active network connection to SS. |
| updateApproaches | `User $user, array $approaches` | `User` | UC-PRV-012 | No | — | Writes `profile_meta.approaches`. |
| updateLicensedStates | `User $user, array $states` | `User` | UC-PRV-100 | No | — | Writes `user_meta.licensed_states` (JSON). |
| updateDemographics | `User $user, array $demographics` | `User` | UC-PRV-100 | No | — | Writes `user_meta.demographics`. |
| updateLanguagesAndWebsite | `User $user, array $languages, ?string $website` | `User` | UC-PRV-100 | No | — | Writes `user_meta.languages` (+ optional `website` string). |
| updateNetworkPartners | `User $user, array $partners` | `User` | UC-PRV-100 | No | — | Writes `user_meta.network_partners`. |
| updateAiSettings | `User $user, array $settings` | `User` | UC-PRV-105 | No | — | Writes `user_meta.ai_shadow_settings` (JSON). |
| **setMetaPublic** | `User $user, string $key, mixed $value, string $type = 'json'` | `void` | UC-PRV-108 | No | — | **Public wrapper around private `setMeta`.** Used by `NetworkController::saveNetworkConfig/resetNetworkConfig`. Uses explicit first-or-create (never `updateOrCreate` with `id` in payload — UUID-PK write pitfall). Manual id: `'um_' . Str::lower(Str::random(12))`. |

**⚠️ `setMeta` write contract:** first-or-create pattern only. On update, `$existing->update(['meta_value'=>..., 'meta_type'=>...])`; on create, generate id manually. Never include `id` in an `updateOrCreate` payload — the UUID PK causes silent-fail / duplicate-key errors on the update branch.

---

## `app/Services/SupportService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| createTicket | `User $submitter, array $data` | `Complaint` | UC-XP-040 (channel 1: help ticket) | Yes → admin feed | `TicketCreated` | Category: `support_ticket`. Priority defaults to `normal`. |
| submitFeedback | `User $submitter, array $data, SubmissionChannel $channel` | `Complaint` | UC-XP-040 (channels 2,3) | Yes → admin feed | — | Category: `feedback`. Channel: `contextual_questionnaire` or `free_form`. |
| reply | `Complaint $complaint, User $author, string $body, bool $internalNote = false` | `ComplaintReply` | UC-XP-041 | Yes → submitter feed (if not internal) | `TicketReplied` | |
| listForUser | `User $user, array $filters = []` | `Collection<Complaint>` | UC-XP-042 | No | — | Returns submitter's own complaints. |
| getHelpArticles | `?string $category = null` | `Collection<HelpArticle>` | UC-XP-043 | No | — | Public help library. |
| closeTicket | `Complaint $ticket, User $submitter` | `bool` | UC-CS-103, UC-BP-112, UC-SS-083 | No | — | Submitter closes own ticket. |
| submitFeedback | `User $user, string $channel, string $body, array $meta = []` | `Complaint` | UC-PRV-184, UC-CS-104, UC-CS-105, UC-BP-113, UC-BP-114, UC-SS-084, UC-SS-085 | No | — | channel: feedback_button|contextual_questionnaire. Routes to feedback queue, not support. |

---

## `app/Services/AdminUserService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| list | `array $filters = [], int $page = 1, int $perPage = 25` | `LengthAwarePaginator<User>` | UC-ADM-020 | No | — | Admin filters: role, tier, locked, search. |
| view | `User $target, User $admin` | `User` | UC-ADM-021 | No (audit log row written) | — | Writes `admin_audit_log` `action=view_user`. |
| lock | `User $target, User $admin, string $reason` | `bool` | UC-ADM-023 | Yes → target feed | `UserLocked` | Sets `locked_at`, `locked_reason`. Forces logout. Writes audit log. |
| unlock | `User $target, User $admin` | `bool` | UC-ADM-024 | Yes → target feed | — | Clears `locked_at`. |
| forcePasswordReset | `User $target, User $admin` | `bool` | UC-ADM-025 | Yes → target feed | `PasswordReset` | Generates token, dispatches email. |
| changeRole | `User $target, UserRole $newRole, User $admin` | `bool` | UC-ADM-026 | Yes → target feed | `UserRoleChanged` | Adds or sets default in `user_roles`. Audit log. |
| deactivate | `User $target, User $admin, ?string $reason = null` | `bool` | UC-ADM-027 | Yes → target feed | — | Sets `deactivated_at`. Soft — preserves data. |
| reactivate | `User $target, User $admin` | `bool` | UC-ADM-028 | Yes → target feed | — | Clears `deactivated_at`. |
| impersonate | `User $target, User $admin` | `string` *(session token)* | UC-ADM-022 | Yes → target feed | — | Audit-logged. Dev/support tool — sets impersonation cookie for the admin session. |

---

## `app/Services/AdminPackageService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| listPackages | `void` | `Collection<PackageOverride>` | UC-ADM-040 | No | — | |
| createOverride | `array $data, User $admin` | `PackageOverride` | UC-ADM-041 | No (audit log) | — | Promotional pricing window. |
| updateOverride | `PackageOverride $pkg, array $data, User $admin` | `bool` | UC-ADM-042 | No (audit log) | — | |
| expireOverride | `PackageOverride $pkg, User $admin` | `bool` | UC-ADM-043 | No (audit log) | — | |
| toggleFeatureFlag | `Package $package, string $flagKey, bool $enabled, User $admin` | `bool` | UC-ADM-012 | No | — | Writes `package_overrides.feature_flags` JSON. |
| setLimits | `Package $package, array $limits, User $admin` | `bool` | UC-ADM-013 | No | — | Writes `package_overrides.limits` JSON. |

---

## `app/Services/AdminComplaintService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| list | `array $filters = [], int $page = 1, int $perPage = 25` | `LengthAwarePaginator<Complaint>` | UC-ADM-060 | No | — | Filters: category, status, priority, channel. |
| view | `Complaint $complaint` | `Complaint` | UC-ADM-051 | No | — | Eager-loads replies + meta. |
| assign | `Complaint $complaint, User $assignee, User $admin` | `bool` | UC-ADM-052 | Yes → assignee feed | — | |
| changeStatus | `Complaint $complaint, ComplaintStatus $status, User $admin, ?string $note = null` | `bool` | UC-ADM-055 | Yes → submitter feed | — | Audit log row. |
| changePriority | `Complaint $complaint, ComplaintPriority $priority, User $admin` | `bool` | UC-ADM-064 | No (audit log) | — | |
| reply | `Complaint $complaint, User $admin, string $body, bool $internal = false` | `ComplaintReply` | UC-ADM-053 | Yes → submitter (if not internal) | `TicketReplied` | |
| resolve | `Complaint $complaint, User $admin, string $resolutionNote` | `bool` | UC-ADM-055 | Yes → submitter feed | — | Status → `resolved`. Sets `resolved_at`. |
| close | `Complaint $complaint, User $admin` | `bool` | UC-ADM-055 | Yes → submitter feed | — | Status → `closed`. Sets `closed_at`. |
| changePriority | `Complaint $complaint, ComplaintPriority $priority, User $admin` | `bool` | UC-ADM-064 | No (audit log) | — | Writes `admin_audit_log`. Internal admin tool. |

---

## `app/Services/AdminPaymentService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| listTransactions | `array $filters = [], int $page = 1` | `LengthAwarePaginator<PractitionerPayment>` | UC-ADM-070 | No | — | |
| viewTransaction | `PractitionerPayment $payment` | `PractitionerPayment` | UC-ADM-071 | No | — | |
| refund | `PractitionerPayment $payment, int $amountCents, User $admin, string $reason` | `bool` | UC-ADM-072 | Yes → practitioner feed | `RefundProcessed` | Calls Stripe Refund API. Updates `refunded_cents`, status. Audit log. |
| stripeSnapshot | `void` | `array` | UC-ADM-073 | No | — | Aggregates: MRR, ARR, churn, active subs. |
| listFailedPayments | `int $days = 7` | `Collection<PractitionerPayment>` | UC-ADM-074 | No | — | |
| listWebhookEvents | `array $filters = [], int $page = 1` | `LengthAwarePaginator<StripeWebhookEvent>` | UC-ADM-046 | No | — | Idempotency log view. |
| listTransactions | `array $filters = [], int $page = 1` | `LengthAwarePaginator<PractitionerPayment>` | UC-ADM-070 | No | — | Filterable list of all payments. |
| viewTransaction | `PractitionerPayment $payment` | `PractitionerPayment` | UC-ADM-071 | No | — | Eager-loads payer, method, invoice. |
| refund | `PractitionerPayment $payment, int $amountCents, User $admin, string $reason` | `bool` | UC-ADM-072 | Yes → practitioner | `RefundProcessed` | Stripe Refund API. Writes `admin_audit_log`. |
| stripeSnapshot | `void` | `array` | UC-ADM-073 | No | — | Aggregates: MRR, ARR, churn, active subs, payouts pending. |
| listFailedPayments | `int $days = 7` | `Collection<PractitionerPayment>` | UC-ADM-074 | No | — | For dunning workflow. |

---

## `app/Services/AdminRoleService.php`

| Method | Parameters | Returns | UCs | ActivityService::log() | Events fired | Notes |
|---|---|---|---|---|---|---|
| listRoles | `void` | `Collection<Role>` | UC-ADM-050 | No | — | |
| createRole | `array $data, User $admin` | `Role` | UC-ADM-051 | No (audit log) | — | |
| updatePermissions | `Role $role, array $permissionKeys, User $admin` | `bool` | UC-ADM-052 | No (audit log) | — | Replaces `role_permissions` rows. |
| delete | `Role $role, User $admin` | `bool` | UC-ADM-053 | No (audit log) | — | Cannot delete system roles. |
| assignToUser | `User $target, Role $role, User $admin` | `bool` | UC-ADM-054 | Yes → target feed | — | Writes to `user_roles`. |

---

---

## `app/Services/AdminPayoutService.php`
**UCs:** UC-ADM-044, UC-ADM-045
**Methods:**

| Method | Signature | Returns | UC | Fan-out? | Event | Notes |
|---|---|---|---|---|---|---|
| listPending | `array $filters = [], int $page = 1` | `LengthAwarePaginator<BpPayout\|CsPayout>` | UC-ADM-044 | No | — | Union of `bp_payouts` + `cs_payouts` filtered by `status ∈ {pending, scheduled}`. |
| releaseManually | `string $payoutId, string $payoutType, User $admin, string $reason` | `bool` | UC-ADM-045 | Yes → recipient feed | `PayoutReleasedManually` | Calls Stripe Transfer API. Updates `status=paid`, `paid_at=now`. Writes `admin_audit_log`. |

---

## `app/Services/AdminHelpArticleService.php`
**UCs:** UC-ADM-058, UC-ADM-059, UC-ADM-060
**Methods:**

| Method | Signature | Returns | UC | Fan-out? | Event | Notes |
|---|---|---|---|---|---|---|
| list | `array $filters = []` | `Collection<HelpArticle>` | UC-ADM-058 | No | — | Includes drafts for admins. |
| upsert | `array $data, User $admin, ?string $id = null` | `HelpArticle` | UC-ADM-058 | No | — | Writes `help_articles` row. |
| publish | `HelpArticle $article, bool $published, User $admin` | `bool` | UC-ADM-059 | No | `HelpArticlePublished` (on true) | Sets `published_at`; logs audit. |
| reorder | `array $orderMap, User $admin` | `bool` | UC-ADM-060 | No | — | Updates `sort_order` for each id. |

---

## `app/Services/TaxDocumentService.php`
**UCs:** UC-BP-063, UC-BP-073, UC-BP-074
**Methods:**

| Method | Signature | Returns | UC | Fan-out? | Event | Notes |
|---|---|---|---|---|---|---|
| listForUser | `User $bp, int $year = null` | `Collection<BpTaxDocument>` | UC-BP-061 | No | — | |
| download | `BpTaxDocument $doc, User $actor` | `string` (signed URL) | UC-BP-063 | No | — | 15-min signed URL. |
| updateTaxAddress | `User $bp, array $address` | `bool` | UC-BP-073 | No | — | Writes `user_meta.tax_address` JSON. |
| uploadW9 | `User $bp, UploadedFile $file` | `BpTaxDocument` | UC-BP-074 | No | — | Writes `bp_tax_documents` row `doc_type=w9`; sets `users.w9_status=pending`. |

---

## `app/Services/TeamService.php`
**UCs:** UC-BP-081, UC-BP-082, UC-BP-083, UC-BP-084, UC-BP-085, UC-BP-086
**Methods:**

| Method | Signature | Returns | UC | Fan-out? | Event | Notes |
|---|---|---|---|---|---|---|
| listMembers | `User $agency` | `Collection<BpTeamMember>` | UC-BP-080 | No | — | |
| invite | `User $agency, string $email, array $permissions` | `BpTeamInvitation` | UC-BP-081 | Yes → invitee email | — | Creates pending row + sends invite email. |
| updatePermissions | `BpTeamMember $member, array $permissions, User $actor` | `bool` | UC-BP-082 | No | — | Writes `permissions` JSON. |
| removeMember | `BpTeamMember $member, User $actor` | `bool` | UC-BP-084 | Yes → member | — | Status → `inactive`. Revokes session. |
| setStatus | `BpTeamMember $member, string $status, User $actor` | `bool` | UC-BP-085 | Yes → member | — | Statuses: `active|paused|inactive`. |
| acceptInvitation | `string $token, array $userPayload` | `BpTeamMember` | UC-BP-086 | Yes → agency owner | — | Creates User if needed; writes member row. |



---

## `app/Services/NewsService.php`
**UCs:** UC-PRV-240..248
**Methods:**

| Method | Signature | Returns | UC | Fan-out? | Event | Notes |
|---|---|---|---|---|---|---|
| listFeed | `User $user, array $filters = [], int $page = 1` | `LengthAwarePaginator<NewsPost>` | UC-PRV-240 | No | — | Algorithmic feed: pinned + followed authors + recent + trending. |
| publishPost | `User $author, array $payload` | `NewsPost` | UC-PRV-241 | Yes → followers (digest only) | `NewsPostPublished` | Validates `practitioner_public` toggle is on. |
| editPost | `NewsPost $post, array $payload, User $actor` | `NewsPost` | UC-PRV-241 | No | — | Author or admin only. |
| deletePost | `NewsPost $post, User $actor` | `bool` | UC-PRV-241 | No | — | Soft delete. |
| comment | `NewsPost $post, User $author, string $body` | `NewsComment` | UC-PRV-242 | Yes → post author | `NewsCommented` | Gated by author `notify_news_comments`. |
| deleteComment | `NewsComment $comment, User $actor` | `bool` | UC-PRV-242 | No | — | Author or post-owner or admin only. |
| react | `NewsPost $post, User $user, string $reaction` | `NewsReaction` | UC-PRV-243 | No | — | Toggle: removes existing reaction by same user on same post. |
| voteInPoll | `NewsPost $post, User $user, int $optionIndex` | `NewsPollVote` | UC-PRV-244 | No | — | One vote per user per post. Idempotent. |
| listEvents | `User $user, array $filters = []` | `Collection<NewsEvent>` | UC-PRV-245 | No | — | Upcoming events sorted by `starts_at`. |
| rsvpEvent | `NewsEvent $event, User $user, string $response` | `bool` | UC-PRV-246 | Yes → event organizer | `EventRsvpReceived` | response ∈ {yes, no, maybe}. |
| browseLibrary | `User $user, array $filters = []` | `Collection<NewsLibraryItem>` | UC-PRV-247 | No | — | Curated reference materials. |
| trending | `int $hours = 168` | `Collection<NewsTrendingTopic>` | UC-PRV-248 | No | — | Materialized weekly. |


# Section 4 — Events + Listeners

All events live under `app/Events/` grouped by domain. All listeners live under `app/Listeners/`. Two cross-cutting listeners (`ActivityFanoutListener`, `SendEmailNotificationListener`) subscribe to nearly every event — they look at the event's `recipients()` and `mailTemplate()` methods to do their work.

## Event base contract

Every Aegis event extends `App\Events\AegisEvent` (abstract):

```php
abstract class AegisEvent
{
    abstract public function actorId(): ?string;
    abstract public function recipients(): array;     // [['user_id'=>X,'portal'=>Y], …]
    abstract public function eventType(): string;     // matches activity_events.event_type
    abstract public function severity(): \App\Enums\ActivitySeverity;
    abstract public function payload(): array;        // serializable for activity row + email vars
    public function mailTemplate(): ?string { return null; }   // null = no email
    public function mailGateKey(): ?string { return null; }    // null = ungated; key = user_meta notify_<key>
    public function ungated(): bool { return false; }          // true = bypass user notification gate
}
```

---

## `app/Events/Auth/`

### `UserRegistered.php`
**Constructor:** `(public User $user, public ?string $inviteToken = null)`
**Recipients:** `[['user_id' => $user->id, 'portal' => $user->role->portal()]]`
**Event type:** `account`
**Severity:** `info`
**Mail template:** `emails.auth.welcome`
**Mail gate:** ungated (always send)

### `UserLoggedIn.php`
**Constructor:** `(public User $user, public string $ip, public string $userAgent, public bool $newDevice)`
**Recipients:** self
**Event type:** `account`
**Severity:** `info`
**Mail template:** `emails.auth.new-device` *(when `$newDevice === true`)*
**Mail gate:** `notify_security_new_device`

### `PasswordReset.php`
**Constructor:** `(public User $user, public string $resetToken)`
**Recipients:** self
**Event type:** `account`
**Severity:** `warning`
**Mail template:** `emails.auth.password-reset`
**Mail gate:** ungated

---

## `app/Events/Plan/`

### `PlanSigned.php`
**Constructor:** `(public ContinuityPlan $plan, public User $practitioner)`
**Recipients:** practitioner + all active CS + all active SS for the plan
**Event type:** `attestation`
**Severity:** `info`
**Mail template:** `emails.plan.plan-finalized`
**Mail gate:** `notify_plan_signed`

### `PlanVersionUpdated.php`
**Constructor:** `(public ContinuityPlan $plan, public int $newVersion, public array $changedFields)`
**Recipients:** all active stewards
**Event type:** `compliance`
**Severity:** `info`
**Mail template:** `emails.plan.plan-updated`
**Mail gate:** `notify_plan_updates`

### `VaultAttested.php`
**Constructor:** `(public ContinuityPlan $plan, public User $practitioner, public string $note)`
**Recipients:** practitioner + all active CS + all active SS
**Event type:** `attestation`
**Severity:** `info`
**Mail template:** `emails.plan.vault-attested`
**Mail gate:** `notify_vault_attested`

### `AnnualReviewDue.php`
**Constructor:** `(public ContinuityPlan $plan, public int $daysRemaining)`
**Recipients:** practitioner only
**Event type:** `compliance`
**Severity:** `warning`
**Mail template:** `emails.plan.annual-review-due`
**Mail gate:** `notify_annual_review`

### `AnnualReviewCompleted.php`
**Constructor:** `(public ContinuityPlan $plan, public User $practitioner)`
**Recipients:** practitioner + all active CS + all active SS
**Event type:** `attestation`
**Severity:** `info`
**Mail template:** `emails.plan.annual-review-completed`
**Mail gate:** `notify_annual_review`

---

## `app/Events/Steward/`

### `StewardDesignated.php`
**Constructor:** `(public PlanSteward $designation, public User $invitee, public User $invitedBy)`
**Recipients:** invitee
**Event type:** `account`
**Severity:** `info`
**Mail template:** `emails.steward.cs-invited` *(or `ss-invited` based on `steward_type`)*
**Mail gate:** ungated (designation invite)

### `StewardAccepted.php`
**Constructor:** `(public PlanSteward $designation)`
**Recipients:** practitioner of the plan
**Event type:** `account`
**Severity:** `info`
**Mail template:** `emails.steward.cs-accepted`
**Mail gate:** `notify_steward_response`

### `StewardDeclined.php`
**Constructor:** `(public PlanSteward $designation, public ?string $reason)`
**Recipients:** practitioner of the plan
**Event type:** `account`
**Severity:** `warning`
**Mail template:** `emails.steward.cs-declined`
**Mail gate:** `notify_steward_response`

### `StewardRemoved.php`
**Constructor:** `(public PlanSteward $designation, public User $actor, public string $reason)`
**Recipients:** removed steward + practitioner
**Event type:** `account`
**Severity:** `warning`
**Mail template:** `emails.steward.cs-removed`
**Mail gate:** `notify_steward_changes`

### `AlternateCSActivated.php`
**Constructor:** `(public ContinuityPlan $plan, public PlanSteward $newPrimary, public PlanSteward $demotedPrimary)`
**Recipients:** practitioner + both stewards + any SS on the plan
**Event type:** `compliance`
**Severity:** `warning`
**Mail template:** `emails.steward.alternate-activated`
**Mail gate:** `notify_steward_changes`

---

## `app/Events/Incident/`

### `IncidentReported.php`
**Constructor:** `(public CriticalIncident $incident, public User $reporter)`
**Recipients:** practitioner + all CS + all SS on the plan
**Event type:** `incident`
**Severity:** `warning`
**Mail template:** `emails.incident.incident-reported`
**Mail gate:** ungated (incident emails always send)

### `IncidentVerified.php`
**Constructor:** `(public CriticalIncident $incident, public User $verifier)`
**Recipients:** all parties
**Event type:** `incident`
**Severity:** `warning`
**Mail template:** `emails.incident.incident-verified`
**Mail gate:** ungated

### `IncidentActivated.php`
**Constructor:** `(public CriticalIncident $incident, public User $actor)`
**Recipients:** all parties + admin
**Event type:** `incident`
**Severity:** `critical`
**Mail template:** `emails.incident.incident-activated`
**Mail gate:** ungated

### `VaultUnsealed.php`
**Constructor:** `(public CriticalIncident $incident, public ContinuityPlan $plan)`
**Recipients:** practitioner + all active CS
**Event type:** `vault`
**Severity:** `critical`
**Mail template:** `emails.incident.vault-unsealed`
**Mail gate:** ungated

### `IncidentEscalated.php`
**Constructor:** `(public CriticalIncident $incident, public User $actor, public string $reason)`
**Recipients:** admin + practitioner
**Event type:** `incident`
**Severity:** `critical`
**Mail template:** `emails.incident.incident-escalated`
**Mail gate:** ungated

### `IncidentClosed.php`
**Constructor:** `(public CriticalIncident $incident, public User $actor, public string $closeNote)`
**Recipients:** all parties
**Event type:** `incident`
**Severity:** `info`
**Mail template:** `emails.incident.incident-resolved`
**Mail gate:** ungated

---

## `app/Events/Business/`

### `ProposalAccepted.php`
**Constructor:** `(public BpProposal $proposal, public BpContract $contract)`
**Recipients:** BP (proposal author) + all other proposing BPs on the job (job-filled notice)
**Event type:** `account`
**Severity:** `info`
**Mail template:** `emails.bp.proposal-accepted`
**Mail gate:** `notify_proposal_response`

### `ContractCreated.php`
**Constructor:** `(public BpContract $contract)`
**Recipients:** practitioner + BP
**Event type:** `account`
**Severity:** `info`
**Mail template:** `emails.bp.contract-generated`
**Mail gate:** ungated (legal document)

### `ContractSigned.php`
**Constructor:** `(public BpContract $contract, public User $finalSigner)`
**Recipients:** practitioner + BP
**Event type:** `attestation`
**Severity:** `info`
**Mail template:** `emails.bp.contract-signed`
**Mail gate:** ungated

### `MilestoneSubmitted.php`
**Constructor:** `(public BpMilestone $milestone, public User $submitter)`
**Recipients:** practitioner of contract
**Event type:** `task`
**Severity:** `info`
**Mail template:** `emails.bp.milestone-submitted`
**Mail gate:** `notify_milestone_activity`

### `InvoiceSent.php`
**Constructor:** `(public BpInvoice $invoice)`
**Recipients:** practitioner
**Event type:** `payment`
**Severity:** `info`
**Mail template:** `emails.bp.invoice-sent`
**Mail gate:** ungated (financial)

### `InvoicePaid.php`
**Constructor:** `(public BpInvoice $invoice, public BpInvoicePayment $payment)`
**Recipients:** practitioner + BP
**Event type:** `payment`
**Severity:** `info`
**Mail template:** `emails.bp.invoice-paid`
**Mail gate:** ungated

### `PayoutReleased.php`
**Constructor:** `(public BpPayout|CsPayout $payout)`
**Recipients:** payout recipient
**Event type:** `payment`
**Severity:** `info`
**Mail template:** `emails.bp.payout-released`
**Mail gate:** `notify_payouts`

---

## `app/Events/Support/`

### `TicketCreated.php`
**Constructor:** `(public Complaint $complaint, public User $submitter)`
**Recipients:** admin queue (any active admin) + submitter (acknowledgment)
**Event type:** `system`
**Severity:** `info`
**Mail template:** `emails.support.ticket-received`
**Mail gate:** ungated (acknowledgment)

### `TicketReplied.php`
**Constructor:** `(public Complaint $complaint, public ComplaintReply $reply)`
**Recipients:** complaint submitter (if reply not internal)
**Event type:** `system`
**Severity:** `info`
**Mail template:** `emails.support.ticket-replied`
**Mail gate:** `notify_support_replies`

---

## `app/Events/Admin/`

### `UserLocked.php`
**Constructor:** `(public User $target, public User $admin, public string $reason)`
**Recipients:** target user
**Event type:** `account`
**Severity:** `critical`
**Mail template:** `emails.admin.account-locked`
**Mail gate:** ungated

### `UserRoleChanged.php`
**Constructor:** `(public User $target, public UserRole $oldRole, public UserRole $newRole, public User $admin)`
**Recipients:** target user
**Event type:** `account`
**Severity:** `warning`
**Mail template:** `emails.admin.role-changed`
**Mail gate:** ungated

### `RefundProcessed.php`
**Constructor:** `(public PractitionerPayment $payment, public int $refundedCents, public User $admin)`
**Recipients:** practitioner
**Event type:** `payment`
**Severity:** `info`
**Mail template:** `emails.admin.refund-processed`
**Mail gate:** ungated

---

## Listeners

### `app/Listeners/ActivityFanoutListener.php`

**Subscribes to:** every event extending `AegisEvent` (registered via `EventServiceProvider::$subscribe`)

```php
public function handle(AegisEvent $event): void
{
    // Reads $event->recipients(), $event->eventType(), $event->severity(),
    // $event->actorId(), $event->payload() — writes one activity_events row
    // per recipient via ActivityService::logFanout().
}
```

**Notes:** Queued on the `default` queue. Idempotent — uses `event_id + recipient_id` as a soft dedupe key in `payload`.

---

### `app/Listeners/SendEmailNotificationListener.php`

**Subscribes to:** every event extending `AegisEvent` where `mailTemplate() !== null`

```php
public function handle(AegisEvent $event): void
{
    // For each recipient in $event->recipients():
    //   - If $event->ungated() OR NotificationService::isGated(user, $event->mailGateKey()) returns true:
    //     dispatch SendEmailJob(user_id, $event->mailTemplate(), $event->payload())
}
```

**Notes:** Queued on the `email` queue (lower priority than incident queue).

---

### `app/Listeners/SendIncidentAlertsListener.php`

**Subscribes to:** `IncidentActivated`, `VaultUnsealed`, `IncidentEscalated` only

```php
public function handle(IncidentActivated|VaultUnsealed|IncidentEscalated $event): void
{
    // Dispatches IncidentNotificationJob to the high-priority "incident" queue.
    // Ungated — runs BEFORE the generic SendEmailNotificationListener and skips
    // the user_meta notification gate check entirely.
}
```

**Notes:** Queued on the `incident` queue (highest priority — bypasses normal queue order).

---

### `app/Listeners/StripeEventListener.php`

**Subscribes to:** Cashier's `Laravel\Cashier\Events\WebhookReceived`, `WebhookHandled`

```php
public function handleReceived(WebhookReceived $event): void
{
    // 1. Write stripe_webhook_events row (idempotency log)
    // 2. Branch by $event->payload['type']:
    //    - invoice.payment_succeeded → InvoiceService::markPaid()
    //    - account.updated → User::update stripe state
    //    - transfer.created → PayoutService::markInTransit()
    //    - transfer.paid → PayoutService::markPaid()
    //    - charge.refunded → AdminPaymentService logs
    // 3. Mark stripe_webhook_events.processed_at on success
}
```

**Notes:** Queued on the `default` queue. Idempotent via the `stripe_event_id` UQ constraint — if the row exists with `processed_at`, the handler short-circuits.

---


---

## Additional Events (added in corrected version)

### `app/Events/Steward/StewardResigned.php`
**UC:** UC-CS-026
**Payload:** `PlanSteward $designation, string $reason, ?Collection $suggestedAlternates = null`
**Listeners:** `ActivityFanoutListener`, `SendEmailNotificationListener`
**Email template:** `emails/steward/cs-resigned.blade.php`
**Notes:** When CS resigns, `suggestedAlternates` lists active alternates on the same plan for the practitioner to choose from.

### `app/Events/Incident/IncidentReopened.php`
**UC:** UC-CS-049
**Payload:** `CriticalIncident $incident, User $actor, string $reason`
**Listeners:** `ActivityFanoutListener`, `SendIncidentAlertsListener`
**Email template:** `emails/incident/incident-reopened.blade.php`

### `app/Events/Incident/IncidentWithdrawn.php`
**UC:** UC-SS-044
**Payload:** `CriticalIncident $incident, User $ss, string $reason`
**Listeners:** `ActivityFanoutListener`, `SendEmailNotificationListener`
**Email template:** `emails/incident/incident-resolved.blade.php` (reused with withdrawal flag)

### `app/Events/Admin/HelpArticlePublished.php`
**UC:** UC-ADM-059
**Payload:** `HelpArticle $article, User $admin`
**Listeners:** none (informational only; future digest hook)

### `app/Events/Admin/PayoutReleasedManually.php`
**UC:** UC-ADM-045
**Payload:** `BpPayout|CsPayout $payout, User $admin, string $reason`
**Listeners:** `ActivityFanoutListener`, `SendEmailNotificationListener`
**Email template:** `emails/admin/manual-payout-released.blade.php`

### `app/Events/Auth/EmailVerified.php`
**UC:** UC-PRV-213
**Payload:** `User $user`
**Listeners:** none (state change only)

### `app/Events/Business/ContractCancelled.php`
**UC:** UC-BP-035, UC-PRV-138
**Payload:** `BpContract $contract, User $actor, string $reason`
**Listeners:** `ActivityFanoutListener`, `SendEmailNotificationListener`

### `app/Events/Business/InvoiceVoided.php`
**UC:** UC-BP-055, UC-CS-084
**Payload:** `BpInvoice|CsInvoice $invoice, User $actor`
**Listeners:** `ActivityFanoutListener`, `SendEmailNotificationListener`

### `app/Events/Business/ProposalSubmitted.php`
**UC:** UC-BP-030
**Payload:** `BpProposal $proposal`
**Listeners:** `ActivityFanoutListener` (practitioner feed)

### `app/Events/Business/ProposalWithdrawn.php`
**UC:** UC-BP-032
**Payload:** `BpProposal $proposal, User $bp, string $reason`
**Listeners:** `ActivityFanoutListener` (practitioner feed)



### `app/Events/News/NewsPostPublished.php`
**UC:** UC-PRV-241
**Payload:** `NewsPost $post, User $author`
**Listeners:** `ActivityFanoutListener` (digest only — not real-time)

### `app/Events/News/NewsCommented.php`
**UC:** UC-PRV-242
**Payload:** `NewsComment $comment, NewsPost $post, User $author`
**Listeners:** `ActivityFanoutListener`, `SendEmailNotificationListener` (gated by `notify_news_comments`)

### `app/Events/News/EventRsvpReceived.php`
**UC:** UC-PRV-246
**Payload:** `NewsEvent $event, User $respondent, string $response`
**Listeners:** `ActivityFanoutListener` (organizer feed only)


# Section 5 — Jobs

All jobs under `app/Jobs/`. Implement `ShouldQueue`. Queue assignment chosen by latency criticality.

---

### `app/Jobs/ActivityFanoutJob.php`
**Queue:** `default`
**Constructor:** `(string $eventClass, array $serializedPayload, array $recipients)`
**Handles:** Background fan-out of an event to N recipient `activity_events` rows.
**Triggers:** dispatched by `ActivityFanoutListener` when recipient count > 5 (sync write for small fanouts, queued for large).
**UCs:** UC-XP-001..010

---

### `app/Jobs/SendEmailJob.php`
**Queue:** `email`
**Constructor:** `(string $recipientId, string $templateName, array $vars)`
**Handles:** Renders Blade mail template, sends via SES. Logs delivery result to `user_meta` (`last_email_$template`).
**Retries:** 3, exponential backoff.

---

### `app/Jobs/IncidentNotificationJob.php`
**Queue:** `incident` (highest priority)
**Constructor:** `(string $incidentId, string $eventType, array $recipientIds)`
**Handles:** Ungated incident emails. Bypasses notification gates. Sends to SES with high priority headers.
**UCs:** UC-CS-040, UC-CS-042, UC-SS-040, UC-SS-041

---

### `app/Jobs/AnnualReviewReminderJob.php`
**Queue:** `default`
**Constructor:** `(string $planId)`
**Handles:** Scheduled — fires `AnnualReviewDue` event for plans where `annual_review_date - now <= 30d` and `status = 'active'`.
**Scheduled:** daily at 09:00 UTC via `app/Console/Kernel.php`.
**UCs:** UC-XP-014

---

### `app/Jobs/VaultSealCheckJob.php`
**Queue:** `default`
**Constructor:** `()`
**Handles:** Sweep — finds plans where active incident has been `closed` for >5min but `vault_attested_at` not refreshed; re-seals vault by removing the active-incident gate.
**Scheduled:** every 5 minutes.
**UCs:** UC-XP-003

---

### `app/Jobs/StaleIncidentAlertJob.php`
**Queue:** `incident`
**Constructor:** `()`
**Handles:** Sweep — finds incidents in `verified` status >24h without activation. Fires `IncidentEscalated`. Mirrors mark-stale logic in `IncidentService::markStaleIfNeeded()`.
**Scheduled:** hourly.
**UCs:** UC-XP-008

---

### `app/Jobs/DigestEmailJob.php`
**Queue:** `digest` (low priority)
**Constructor:** `(string $userId, string $period)` *(period: 'daily'|'weekly')*
**Handles:** Aggregates user's `activity_events` since last digest, renders `emails.digest.weekly-digest`, sends.
**Scheduled:** weekly Mondays at 08:00 user-local time.
**UCs:** UC-XP-013

---

### `app/Jobs/StripeWebhookProcessorJob.php`
**Queue:** `default`
**Constructor:** `(array $stripePayload)`
**Handles:** Idempotent webhook processing. Cashier dispatches via `StripeEventListener`. Implements the branch logic for `invoice.payment_succeeded`, `account.updated`, etc.

---

# Section 6 — Policies

All policies under `app/Policies/`. Registered in `AuthServiceProvider::$policies`. Use Laravel's `Gate::authorize()` or controller `$this->authorize()`.

---

## `app/Policies/ContinuityPlanPolicy.php`

| Method | Who can | Gate condition | UC |
|---|---|---|---|
| view | practitioner | owns the plan | UC-PRV-030 |
| view | CS/SS | is active steward on the plan | UC-CS-012 |
| view | admin | always | UC-ADM-021 |
| update | practitioner | owns AND status ∈ {draft, active} | UC-PRV-035 |
| sign | practitioner | owns AND status = draft AND ≥1 active CS exists | UC-PRV-036 |
| beginAnnualReview | practitioner | owns AND status ∈ {active, annual_review_due} | UC-PRV-038 |
| completeAnnualReview | practitioner | owns AND status = annual_review_due | UC-PRV-039 |
| delete | practitioner | owns AND status = draft | UC-PRV-030 |
| configureIncidents | practitioner | owns AND status ∈ {draft, active} | UC-PRV-031 |

---

## `app/Policies/VaultPolicy.php`

| Method | Who can | Gate condition | UC |
|---|---|---|---|
| view | practitioner | owns the plan | UC-PRV-070 |
| viewContents | CS/SS | active incident exists for this plan AND is assigned steward AND `plan_stewards.vault_access` ∈ {scoped, full} | UC-CS-061 |
| upload | practitioner | owns AND plan.status = active | UC-PRV-070 |
| reveal | CS | active incident exists AND `vault_access` = full | UC-CS-061 |
| download | CS/SS | active incident exists AND assigned steward AND `vault_access` ∈ {scoped, full} | UC-CS-062 |
| delete | practitioner | owns the item | UC-PRV-070 |
| attest | practitioner | owns plan AND plan.status = active | UC-PRV-040 |
| setPermissions | practitioner | owns plan | UC-PRV-070 |

---

## `app/Policies/IncidentPolicy.php`

| Method | Who can | Gate condition | UC |
|---|---|---|---|
| view | practitioner | owns the plan | UC-CS-040 |
| view | CS/SS | is assigned steward on the plan | UC-CS-040 |
| view | admin | always | UC-ADM-068 |
| report | CS/SS | is active steward on the plan AND no incident currently `active` for plan | UC-SS-040 |
| verify | CS | is Primary CS on the plan AND incident.status = reported | UC-CS-041 |
| verify | admin | always | UC-ADM-068 |
| activate | CS | is Primary CS AND incident.status = verified | UC-CS-042 |
| close | CS | is Primary CS AND incident.status = active | UC-CS-043 |
| escalate | SS | is assigned SS on the plan | UC-SS-041 |
| addTask | CS/SS | is assigned steward AND incident.status ∈ {verified, active} | UC-CS-050 |

---

## `app/Policies/PlanTaskPolicy.php`

| Method | Who can | Gate condition | UC |
|---|---|---|---|
| view | CS/SS | is assigned steward of matching type | UC-CS-020 |
| update | practitioner | owns the plan | UC-PRV-033 |
| complete | CS/SS | is assigned steward AND `assigned_to` matches steward_type | UC-CS-021 |
| delete | practitioner | owns AND task is open | UC-PRV-033 |

---

## `app/Policies/ContinuityDocumentPolicy.php`

| Method | Who can | Gate condition | UC |
|---|---|---|---|
| view | practitioner | owns the plan | UC-PRV-037 |
| view | CS | is active CS on the plan | UC-CS-070 |
| view | SS | is active SS on the plan | UC-SS-070 |
| download | practitioner | owns | UC-PRV-037 |
| download | CS/SS | is active steward | UC-CS-070 |
| sign | CS | document.status = countersign AND user is the named CS | UC-CS-070 |
| archive | practitioner | owns AND document.status ∈ {active} | UC-PRV-037 |

---

## `app/Policies/ReferralPolicy.php`

| Method | Who can | Gate condition | UC |
|---|---|---|---|
| view | practitioner | from_user_id = user OR to_user_id = user | UC-PRV-120 |
| create | practitioner | always (any practitioner can send) | UC-PRV-121 |
| accept | practitioner | to_user_id = user AND status = sent | UC-PRV-122 |
| decline | practitioner | to_user_id = user AND status = sent | UC-PRV-123 |
| close | practitioner | from_user_id = user AND status = accepted | UC-PRV-124 |
| cancel | practitioner | from_user_id = user AND status = sent | UC-PRV-125 |

---

## `app/Policies/MessagePolicy.php`

| Method | Who can | Gate condition | UC |
|---|---|---|---|
| view | any | user is in thread.participant_ids | UC-PRV-160 |
| send | any | user is in thread.participant_ids AND thread not archived | UC-PRV-161 |
| createThread | any | recipient is a network connection OR steward relationship exists OR contract exists | UC-PRV-160 |
| archive | any | user is in thread.participant_ids | UC-PRV-170 |

---

## `app/Policies/ServicePolicy.php`

| Method | Who can | Gate condition | UC |
|---|---|---|---|
| view | any | service.status = active OR user owns the service | UC-PRV-180 |
| create | practitioner | services_mode = 1 | UC-PRV-180 |
| update | practitioner | owns the service | UC-PRV-181 |
| delete | practitioner | owns | UC-PRV-183 |
| request | any | service.status = active AND requester != owner | UC-PRV-185 |
| acceptRequest | practitioner | owns the service | UC-PRV-186 |

---

## `app/Policies/BpJobPolicy.php`

| Method | Who can | Gate condition | UC |
|---|---|---|---|
| view | practitioner | created_by_id = user | UC-PRV-150 |
| view | BP | job.status = open OR has proposal on it | UC-BP-020 |
| create | practitioner | always | UC-PRV-150 |
| publish | practitioner | owns AND status = draft | UC-PRV-151 |
| close | practitioner | owns AND status ∈ {open, paused} | UC-PRV-154 |
| save | BP | job.status = open | UC-BP-022 |

---

## `app/Policies/BpContractPolicy.php`

| Method | Who can | Gate condition | UC |
|---|---|---|---|
| view | practitioner | practitioner_id = user | UC-BP-040 |
| view | BP | bp_id = user OR is agency owner of bp | UC-BP-040 |
| signByPractitioner | practitioner | practitioner_id = user AND no practitioner signature yet | UC-BP-041 |
| signByBp | BP | bp_id = user AND no BP signature yet | UC-BP-042 |
| cancel | either party | is practitioner or bp AND status ∈ {draft, active} | UC-BP-035 |
| reassign | BP agency owner | bp.bp_type = agency AND user owns the agency | UC-BP-048 |

---

## `app/Policies/BpInvoicePolicy.php`

| Method | Who can | Gate condition | UC |
|---|---|---|---|
| view | practitioner | practitioner_id = user | UC-BP-060 |
| view | BP | bp_id = user | UC-BP-060 |
| create | BP | bp_id = user AND contract exists | UC-BP-060 |
| send | BP | bp_id = user AND invoice.status = draft | UC-BP-062 |
| approve | practitioner | practitioner_id = user AND status ∈ {sent, overdue} | UC-PRV-160 |
| dispute | practitioner | practitioner_id = user AND status ∈ {sent, overdue} | UC-PRV-161 |
| void | BP | bp_id = user AND status ∈ {draft, sent} | UC-BP-055 |

---

## `app/Policies/ComplaintPolicy.php`

| Method | Who can | Gate condition | UC |
|---|---|---|---|
| view | submitter | submitted_by_id = user | UC-XP-042 |
| view | admin | always | UC-ADM-060 |
| reply | submitter | submitted_by_id = user AND status != closed | UC-XP-041 |
| reply | admin | always (can post internal notes) | UC-ADM-053 |
| changeStatus | admin | always | UC-ADM-055 |
| assign | admin | always | UC-ADM-052 |

---

## `app/Policies/AdminPolicy.php`

| Method | Who can | Gate condition | UC |
|---|---|---|---|
| accessAdminPortal | admin | user.role = admin AND not locked | UC-ADM-001 |
| lockUsers | admin | always | UC-ADM-023 |
| changeRoles | admin | always | UC-ADM-026 |
| refundPayments | admin | always | UC-ADM-072 |
| impersonate | admin | target != admin | UC-ADM-022 |
| manageRoles | admin | always | UC-ADM-050 |
| managePackages | admin | always | UC-ADM-040 |

---

## `app/Policies/ProfileEditAuthorizationPolicy.php`

| Method | Who can | Gate condition | UC |
|---|---|---|---|
| view | practitioner | practitioner_id = user | UC-XP-020 |
| view | authorized | authorized_user_id = user | UC-XP-020 |
| grant | practitioner | practitioner_id = user | UC-XP-020 |
| revoke | practitioner | practitioner_id = user | UC-XP-020 |
| editProfileFor | authorized | authorized_user_id = user AND status = active AND expires_at > now | UC-XP-020 |

---


---

## `app/Policies/SubscriptionPolicy.php` *(new)*

| Method | Allowed Subject | Condition | UC |
|---|---|---|---|
| viewBilling | user themselves | always | UC-PRV-140 |
| changeTier | user themselves | user.role ∈ {practitioner, cs, bp} | UC-PRV-003, UC-CS-114 |
| manageAddons | user themselves | user.role = practitioner AND user.tier = practice | UC-PRV-209 |
| cancel | user themselves | always | UC-PRV-145, UC-BP-106 |
| addPaymentMethod | user themselves | always | UC-PRV-141 |
| setAutopay | user themselves | always | UC-PRV-144 |

## `app/Policies/NetworkConnectionPolicy.php` *(new)*

| Method | Allowed Subject | Condition | UC |
|---|---|---|---|
| request | requester user | both users active | UC-PRV-100 |
| accept | recipient user | request.recipient_id = user.id AND status = pending | UC-PRV-101 |
| decline | recipient user | same as accept | UC-PRV-102 |
| archive | either party | status = active | UC-PRV-103 |
| addShadow | requester user | always | UC-PRV-105 |

## `app/Policies/HelpArticlePolicy.php` *(new)*

| Method | Allowed Subject | Condition | UC |
|---|---|---|---|
| view | any authenticated user | article.published_at IS NOT NULL OR user.role = admin | UC-CS-106, UC-BP-115, UC-SS-086 |
| manage | admin only | user.role = admin | UC-ADM-058, UC-ADM-059, UC-ADM-060 |

## `app/Policies/PackagePolicy.php` *(new)*

| Method | Allowed Subject | Condition | UC |
|---|---|---|---|
| view | admin | always | UC-ADM-010 |
| manage | admin | user.role = admin | UC-ADM-011, UC-ADM-012, UC-ADM-013 |


# Section 7 — Middleware

All middleware under `app/Http/Middleware/`. Aliased in `bootstrap/app.php` (Laravel 11 style).

---

### `app/Http/Middleware/EnsureRole.php`
**Alias:** `role`
**Signature:** `handle(Request $request, Closure $next, string ...$roles): mixed`
**Behavior:** Aborts 403 if `$request->user()->role->value` not in `$roles`. Used as `middleware('role:practitioner')`.

---

### `app/Http/Middleware/EnsureAdminRole.php`
**Alias:** `admin`
**Signature:** `handle(Request $request, Closure $next): mixed`
**Behavior:** Shortcut for `role:admin`. Also checks `not impersonating` (prevents nested impersonation).

---

### `app/Http/Middleware/EnsurePlanActive.php`
**Alias:** `plan.active`
**Signature:** `handle(Request $request, Closure $next): mixed`
**Behavior:** Aborts 403 if practitioner's `ContinuityPlan->status !== Active`. Used on routes that require a signed/active plan (vault upload, incident report).

---

### `app/Http/Middleware/EnsureIncidentActive.php`
**Alias:** `incident.active`
**Signature:** `handle(Request $request, Closure $next): mixed`
**Behavior:** Used on CS vault routes — aborts 403 unless an active incident exists for the steward's assigned plan.

---

### `app/Http/Middleware/EnsureServicesMode.php`
**Alias:** `services.mode`
**Signature:** `handle(Request $request, Closure $next): mixed`
**Behavior:** Aborts 403 if `$user->services_mode === false`. Used on `/provider/services/*` routes.

---

### `app/Http/Middleware/CheckAccountLocked.php`
**Alias:** `check.locked`
**Signature:** `handle(Request $request, Closure $next): mixed`
**Behavior:** Logs user out and redirects to `/locked` if `$user->locked_at !== null` or `$user->deactivated_at !== null`. Applied globally after `auth`.

---

### `app/Http/Middleware/ImpersonateForDemo.php`
**Alias:** `demo.impersonate`
**Signature:** `handle(Request $request, Closure $next): mixed`
**Behavior:** Dev/demo only. If `?as=<user_id>` present AND host is in `config('aegis.demo_hosts')`, swap `Auth::setUser()` for the duration of the request. Persistence handled by Inertia shared `as` param.
**Notes:** Disabled in production via `APP_ENV !== 'production'` guard.

---

### `app/Http/Middleware/HandleInertiaRequests.php`

The Inertia request handler. Shares global props on every request.

```php
<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            // Authenticated user shape
            'auth' => fn () => [
                'user' => $user ? [
                    'id'              => $user->id,
                    'display_name'    => $user->display_name,
                    'email'           => $user->email,
                    'avatar_initials' => $user->avatar_initials,
                    'slug'            => $user->slug,
                    'role'            => $user->role?->value,
                    'tier'            => $user->tier,
                    'cs_account_type' => $user->cs_account_type,
                    'bp_type'         => $user->bp_type,
                    'services_mode'   => (bool) $user->services_mode,
                    'maat_addon'      => (bool) $user->maat_addon,
                    'two_factor_enabled' => (bool) $user->two_factor_enabled,
                ] : null,
                'portal' => $user?->role?->portal(),
                'tier'   => $user?->tier,
                'roles'  => $user?->roles->pluck('role')->all() ?? [],
            ],

            // Global UI state
            'hasEmergency' => fn () => $user
                ? app(\App\Services\IncidentService::class)->hasActiveForUser($user)
                : false,
            'unreadCount' => fn () => $user
                ? app(\App\Services\ActivityService::class)->getUnreadCount($user)
                : 0,
            'activePage' => fn () => $request->route()?->getName(),

            // Flash messages (one-shot — read once, gone next request)
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
                'info'    => $request->session()->get('info'),
            ],

            // Demo controls (dev only)
            'demo' => fn () => [
                'enabled' => app()->environment(['local', 'staging']),
                'as'      => $request->query('as'),
            ],

            // Ziggy named routes — exposed to Vue via tightenco/ziggy
            'ziggy' => fn () => (new \Tighten\Ziggy\Ziggy())->toArray(),
        ]);
    }
}
```

---

# Section 8 — Controllers

All controllers are thin — typically a single service call, then an Inertia response. Method tables below derived from `AEGIS_VUE_STRUCTURE.md` (Inertia prop shapes) and `AEGIS_USE_CASES_OUTPUT.md` (UC IDs).

---

## `app/Http/Controllers/Auth/`

### `LoginController.php`
| Method | HTTP | Route | View / Service | UC |
|---|---|---|---|---|
| show | GET | `/login` | `Auth/Login` | UC-PRV-002 |
| store | POST | `/login` | `AuthService::login()` → redirect intended | UC-PRV-002 |
| destroy | POST | `/logout` | `AuthService::logout()` → redirect `/login` | UC-PRV-002 |

### `RegisterController.php`
| Method | HTTP | Route | View / Service | UC |
|---|---|---|---|---|
| show | GET | `/register` | `Auth/Register` | UC-PRV-001 |
| store | POST | `/register` | `AuthService::register()` → email verify | UC-PRV-001 |

### `PasswordResetController.php`
| Method | HTTP | Route | View / Service | UC |
|---|---|---|---|---|
| request | GET | `/password/forgot` | `Auth/PasswordRequest` | UC-PRV-005 |
| sendLink | POST | `/password/forgot` | `AuthService::requestPasswordReset()` | UC-PRV-005 |
| show | GET | `/password/reset/{token}` | `Auth/ResetPassword` | UC-PRV-005 |
| store | POST | `/password/reset` | `AuthService::confirmPasswordReset()` | UC-PRV-005 |

### `MfaController.php`
| Method | HTTP | Route | View / Service | UC |
|---|---|---|---|---|
| setup | GET | `/mfa/setup` | `Auth/MfaSetup` ← `enableMfa()` returns secret + QR | UC-PRV-214 |
| confirm | POST | `/mfa/confirm` | `AuthService::confirmMfa()` | UC-PRV-214 |
| disable | POST | `/mfa/disable` | `AuthService::disableMfa()` | UC-PRV-214 |
| challenge | GET | `/mfa/challenge` | `Auth/MfaChallenge` | UC-PRV-002 |
| verify | POST | `/mfa/verify` | TOTP verify; finishes login flow | UC-PRV-002 |

---

## `app/Http/Controllers/Provider/`

### `DashboardController.php`
| Method | HTTP | Route | Vue Page | Service | Policy | UC |
|---|---|---|---|---|---|---|
| index | GET | `/provider/dashboard` | `Provider/Dashboard` | `DashboardService::getStats()` + `PlanService::getStatus()` + `ActivityService::getRecent()` | — | UC-PRV-090 |

### `ProfileController.php`
| Method | HTTP | Route | Vue Page | Service | Policy | UC |
|---|---|---|---|---|---|---|
| edit | GET | `/provider/edit-profile` | `Provider/EditProfile` | `ProfileService::computeCompletion()` | — | UC-PRV-010 |
| updateBasic | POST | `/provider/profile/basic` | — | `ProfileService::updateBasic()` | own | UC-PRV-010 |
| updateCredentials | POST | `/provider/profile/credentials` | — | `ProfileService::updateCredentials()` | own | UC-PRV-011 |
| updateFees | POST | `/provider/profile/fees` | — | `ProfileService::updateFees()` | own | UC-PRV-015 |
| updateAvailability | POST | `/provider/profile/availability` | — | `ProfileService::updateAvailability()` | own | UC-PRV-016 |
| updateVisibility | POST | `/provider/profile/visibility` | — | `ProfileService::updateVisibility()` | own | UC-PRV-019 |
| lockSlug | POST | `/provider/profile/slug` | — | `ProfileService::lockSlug()` | own | UC-PRV-018 |

### `ContinuityPlanController.php`
| Method | HTTP | Route | Vue Page | Service | Policy | UC |
|---|---|---|---|---|---|---|
| index | GET | `/provider/continuity-plan` | `Provider/ContinuityPlan` | `PlanService::getForPractitioner()` | view | UC-PRV-030 |
| create | POST | `/provider/continuity-plan` | — | `PlanService::createDraft()` | — | UC-PRV-030 |
| update | PATCH | `/provider/continuity-plan/{plan}` | — | `PlanService::update()` | update | UC-PRV-035 |
| configureIncidents | POST | `/provider/continuity-plan/{plan}/incidents` | — | `PlanService::configureIncidents()` | configureIncidents | UC-PRV-031 |
| send | POST | `/provider/continuity-plan/{plan}/send` | — | `PlanService::sendForSignature()` | sign | UC-PRV-035 |
| sign | POST | `/provider/continuity-plan/{plan}/sign` | — | `PlanService::sign()` | sign | UC-PRV-036 |
| beginReview | POST | `/provider/continuity-plan/{plan}/review/begin` | — | `PlanService::beginAnnualReview()` | beginAnnualReview | UC-PRV-038 |
| completeReview | POST | `/provider/continuity-plan/{plan}/review/complete` | — | `PlanService::completeAnnualReview()` | completeAnnualReview | UC-PRV-039 |
| attestVault | POST | `/provider/continuity-plan/{plan}/attest-vault` | — | `PlanService::attestVault()` | attest (VaultPolicy) | UC-PRV-040 |
| addTask | POST | `/provider/continuity-plan/{plan}/tasks` | — | `PlanService::addTask()` | update | UC-PRV-033 |

### `ContinuityStewardController.php`
| Method | HTTP | Route | Vue Page | Service | Policy | UC |
|---|---|---|---|---|---|---|
| index | GET | `/provider/continuity-stewards` | `Provider/ContinuityStewards` | `StewardService::listForPractitioner(type=cs)` | — | UC-PRV-053 |
| inviteExisting | POST | `/provider/continuity-stewards/invite-existing` | — | `StewardService::inviteExisting()` | — | UC-PRV-050 |
| inviteExternal | POST | `/provider/continuity-stewards/invite-external` | — | `StewardService::inviteExternal()` | — | UC-PRV-051 |
| remove | DELETE | `/provider/continuity-stewards/{designation}` | — | `StewardService::remove()` | — | UC-PRV-053 |
| activateAlternate | POST | `/provider/continuity-stewards/{designation}/activate-alternate` | — | `StewardService::activateAlternate()` | — | UC-PRV-052 |

### `SupportStewardController.php`
| Method | HTTP | Route | Vue Page | Service | Policy | UC |
|---|---|---|---|---|---|---|
| index | GET | `/provider/support-stewards` | `Provider/SupportStewards` | `StewardService::listForPractitioner(type=ss)` | — | UC-PRV-054 |
| inviteExternal | POST | `/provider/support-stewards/invite` | — | `StewardService::inviteExternal()` | — | UC-PRV-054 |
| remove | DELETE | `/provider/support-stewards/{designation}` | — | `StewardService::remove()` | — | UC-PRV-054 |

### `NetworkController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/provider/network` | `Provider/Network` | `NetworkService::listConnections()` | UC-PRV-100 |
| request | POST | `/provider/network/request` | — | `NetworkService::request()` | UC-PRV-100 |
| accept | POST | `/provider/network/requests/{req}/accept` | — | `NetworkService::accept()` | UC-PRV-101 |
| decline | POST | `/provider/network/requests/{req}/decline` | — | `NetworkService::decline()` | UC-PRV-102 |
| archive | POST | `/provider/network/{conn}/archive` | — | `NetworkService::archive()` | UC-PRV-103 |
| addShadow | POST | `/provider/network/shadow` | — | `NetworkService::addShadow()` | UC-PRV-105 |
| removeShadow | DELETE | `/provider/network/shadow/{shadowConnection}` | — | `ShadowConnection` delete | UC-PRV-105 |
| inviteShadow | POST | `/provider/network/shadow/{shadow}/invite` | — | `NetworkService::inviteShadowToAegis()` | UC-PRV-106 |
| saveNetworkConfig | PUT | `/provider/network/config` | `Provider/Network` (Config tab) | `ProfileService::setMetaPublic()` (fan-out) | UC-PRV-108 |
| resetNetworkConfig | POST | `/provider/network/config/reset` | `Provider/Network` (Config tab) | `ProfileService::setMetaPublic()` (fan-out) | UC-PRV-108 |

**`index` additionally passes** (beyond `listConnections`): `referralCandidates` (practitioner-only, excludes connected + shadowed), `recommendedShadowProviders` (practitioner-only), `networkConfig` (via private `loadNetworkConfig()`), plus enriched `shadowConnections` with real `match_score` from `network_recommendations`.

**`loadNetworkConfig(User): array`** — private. Reads all config from `users` columns + `user_meta` (via `typed_value` accessor, never raw `meta_type` string compare) + `profile_meta` JSON blob. Returns the `networkConfig` prop consumed by `Network.vue`.

**`saveNetworkConfig` / `resetNetworkConfig`** — single atomic endpoints. Save routes each field to its correct store (see `AEGIS_VUE_STRUCTURE.md` → Network.vue config storage map). Reset clears all config meta keys + resets user columns to empty arrays.

### `ServicesController.php`
| Method | HTTP | Route | Vue Page | Service | Policy | UC |
|---|---|---|---|---|---|---|
| index | GET | `/provider/services` | `Provider/Services` | `ServiceService::listForPractitioner()` | — | UC-PRV-180 |
| store | POST | `/provider/services` | — | `ServiceService::create()` | create | UC-PRV-180 |
| update | PATCH | `/provider/services/{service}` | — | `ServiceService::update()` | update | UC-PRV-181 |
| toggleStatus | POST | `/provider/services/{service}/toggle` | — | `ServiceService::toggleStatus()` | update | UC-PRV-182 |
| destroy | DELETE | `/provider/services/{service}` | — | `ServiceService::delete()` | delete | UC-PRV-183 |
| acceptRequest | POST | `/provider/services/requests/{req}/accept` | — | `ServiceService::acceptRequest()` | acceptRequest | UC-PRV-186 |
| declineRequest | POST | `/provider/services/requests/{req}/decline` | — | `ServiceService::declineRequest()` | acceptRequest | UC-PRV-187 |

### `JobPostingsController.php`
| Method | HTTP | Route | Vue Page | Service | Policy | UC |
|---|---|---|---|---|---|---|
| index | GET | `/provider/job-postings` | `Provider/JobPostings` | `BpJobService::listForPractitioner()` | — | UC-PRV-150 |
| store | POST | `/provider/job-postings` | — | `BpJobService::create()` | create | UC-PRV-150 |
| publish | POST | `/provider/job-postings/{job}/publish` | — | `BpJobService::publish()` | publish | UC-PRV-151 |
| update | PATCH | `/provider/job-postings/{job}` | — | `BpJobService::update()` | publish | UC-PRV-152 |
| pause | POST | `/provider/job-postings/{job}/pause` | — | `BpJobService::pause()` | publish | UC-PRV-153 |
| close | POST | `/provider/job-postings/{job}/close` | — | `BpJobService::close()` | close | UC-PRV-154 |
| reviewProposal | POST | `/provider/job-postings/proposals/{proposal}/review` | — | `ProposalService::markUnderReview()` | — | UC-PRV-131 |
| acceptProposal | POST | `/provider/job-postings/proposals/{proposal}/accept` | — | `ProposalService::accept()` | — | UC-PRV-132 |
| declineProposal | POST | `/provider/job-postings/proposals/{proposal}/decline` | — | `ProposalService::decline()` | — | UC-PRV-133 |

### `ReferralsController.php`
| Method | HTTP | Route | Vue Page | Service | Policy | UC |
|---|---|---|---|---|---|---|
| index | GET | `/provider/referrals` | `Provider/Referrals` | `ReferralService::listForUser()` | view | UC-PRV-120 |
| store | POST | `/provider/referrals` | — | `ReferralService::send()` | create | UC-PRV-121 |
| accept | POST | `/provider/referrals/{ref}/accept` | — | `ReferralService::accept()` | accept | UC-PRV-122 |
| decline | POST | `/provider/referrals/{ref}/decline` | — | `ReferralService::decline()` | decline | UC-PRV-123 |
| close | POST | `/provider/referrals/{ref}/close` | — | `ReferralService::close()` | close | UC-PRV-124 |
| cancel | POST | `/provider/referrals/{ref}/cancel` | — | `ReferralService::cancel()` | cancel | UC-PRV-125 |

### `VaultController.php`
| Method | HTTP | Route | Vue Page | Service | Policy | UC |
|---|---|---|---|---|---|---|
| index | GET | `/provider/vault` | `Provider/Vault` | `VaultService::getForPractitioner()` | view | UC-PRV-070 |
| upload | POST | `/provider/vault/upload` | — | `VaultService::upload()` | upload | UC-PRV-070 |
| update | PATCH | `/provider/vault/{item}` | — | `VaultService::update()` | upload | UC-PRV-070 |
| destroy | DELETE | `/provider/vault/{item}` | — | `VaultService::delete()` | delete | UC-PRV-070 |
| download | GET | `/provider/vault/{item}/download` | — | `VaultService::signedUrl()` | download | UC-PRV-071 |
| attest | POST | `/provider/vault/attest` | — | `PlanService::attestVault()` | attest | UC-PRV-040 |
| setPermissions | POST | `/provider/vault/permissions` | — | `VaultService::setPermissions()` | setPermissions | UC-PRV-070 |

### `DocumentsController.php`
| Method | HTTP | Route | Vue Page | Service | Policy | UC |
|---|---|---|---|---|---|---|
| index | GET | `/provider/important-documents` | `Provider/ImportantDocuments` | `DocumentService::listForPractitioner()` | view | UC-PRV-037 |
| download | GET | `/provider/important-documents/{doc}/download` | — | `DocumentService::signedUrl()` | download | UC-PRV-037 |
| archive | POST | `/provider/important-documents/{doc}/archive` | — | `DocumentService::archive()` | archive | UC-PRV-037 |
| upload | POST | `/provider/important-documents/upload` | — | `DocumentService::uploadLibrary()` | view | UC-PRV-037 |

### `FinancesController.php`
| Method | HTTP | Route | Vue Page | Service | Policy | UC |
|---|---|---|---|---|---|---|
| index | GET | `/provider/finances` | `Provider/Finances` | `InvoiceService::listForPractitioner()` + `SubscriptionService::status()` | — | UC-PRV-160 |
| approveInvoice | POST | `/provider/finances/invoices/{inv}/approve` | — | `InvoiceService::approve()` | approve (BpInvoice) | UC-PRV-160 |
| disputeInvoice | POST | `/provider/finances/invoices/{inv}/dispute` | — | `InvoiceService::dispute()` | dispute | UC-PRV-161 |

### `SettingsController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/provider/settings` | `Provider/Settings` | `ProfileService::getSettings()` + `SubscriptionService::getPricingSnapshot()` | UC-PRV-019 |
| updateNotificationGates | POST | `/provider/settings/notifications` | — | `NotificationService::setGate()` (bulk) | UC-PRV-016 |
| updatePreferences | POST | `/provider/settings/preferences` | — | `ProfileService::updatePreferences()` | UC-PRV-019 |
| upgradeTier | POST | `/provider/settings/upgrade` | — | `SubscriptionService::upgrade()` | UC-PRV-003 |
| downgradeTier | POST | `/provider/settings/downgrade` | — | `SubscriptionService::downgrade()` | UC-PRV-004 |
| toggleServicesMode | POST | `/provider/settings/services-mode` | — | `SubscriptionService::addServicesMode()` / `removeServicesMode()` | UC-PRV-017 |
| toggleMaatAddon | POST | `/provider/settings/maat` | — | `SubscriptionService::addMaatAddon()` / `removeMaatAddon()` | UC-PRV-210 |

---

## `app/Http/Controllers/ContinuitySteward/`

### `DashboardController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/cs/dashboard` | `ContinuitySteward/Dashboard` | `CsDashboardService::getStats()` + `StewardService::listForSteward()` | UC-CS-013 |

### `ProfileController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| edit | GET | `/cs/edit-profile` | `ContinuitySteward/EditProfile` | `ProfileService::computeCompletion()` | UC-CS-001 |
| update | POST | `/cs/profile` | — | `ProfileService::updateBasic()` | UC-CS-002 |

### `ProvidersController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/cs/providers` | `ContinuitySteward/Providers` | `StewardService::listForSteward(cs)` | UC-CS-013 |

### `TasksController.php`
| Method | HTTP | Route | Vue Page | Service | Policy | UC |
|---|---|---|---|---|---|---|
| index | GET | `/cs/my-tasks` | `ContinuitySteward/MyTasks` | `PlanService::tasksForSteward(cs)` | view (PlanTaskPolicy) | UC-CS-020 |
| complete | POST | `/cs/my-tasks/{task}/complete` | — | `PlanService::completeTask()` | complete | UC-CS-021 |
| flagException | POST | `/cs/my-tasks/{task}/exception` | — | `PlanService::flagTaskException()` | complete | UC-CS-022 |

### `ContinuityManagementController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/cs/continuity-management` | `ContinuitySteward/ContinuityManagement` | `StewardService::listForSteward(cs)` + check-in history | UC-CS-013 |
| certify | POST | `/cs/continuity-management/{designation}/certify` | — | `StewardService::certify()` | UC-CS-014 |
| addCheckin | POST | `/cs/continuity-management/{designation}/checkin` | — | `StewardService::addCheckin()` | UC-CS-015 |
| reportIncident | POST | `/cs/continuity-management/{designation}/report-incident` | — | `IncidentService::report()` | UC-CS-040 |
| verifyIncident | POST | `/cs/incidents/{incident}/verify` | — | `IncidentService::verify()` | UC-CS-041 |
| activateIncident | POST | `/cs/incidents/{incident}/activate` | — | `IncidentService::activate()` | UC-CS-042 |
| closeIncident | POST | `/cs/incidents/{incident}/close` | — | `IncidentService::close()` | UC-CS-043 |

### `DocumentsController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/cs/important-documents` | `ContinuitySteward/ImportantDocuments` | `DocumentService::listForSteward()` | UC-CS-070 |
| sign | POST | `/cs/important-documents/{doc}/sign` | — | `DocumentService::addSignature()` | UC-CS-070 |
| download | GET | `/cs/important-documents/{doc}/download` | — | `DocumentService::signedUrl()` | UC-CS-070 |

### `VaultController.php`
| Method | HTTP | Route | Vue Page | Service | Policy | UC |
|---|---|---|---|---|---|---|
| index | GET | `/cs/vault` | `ContinuitySteward/Vault` | `VaultService::getForSteward()` | view | UC-CS-061 |
| reveal | POST | `/cs/vault/{item}/reveal` | — | `VaultService::reveal()` | reveal | UC-CS-061 |
| download | GET | `/cs/vault/{item}/download` | — | `VaultService::signedUrl()` | download | UC-CS-062 |

### `FinancesController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/cs/finances` | `ContinuitySteward/Finances` | `InvoiceService::listForCs()` + Stripe Connect state | UC-CS-080 |
| stripeOnboard | POST | `/cs/finances/stripe/onboard` | — | `PayoutService::startConnectOnboarding()` | UC-CS-080 |

### `SettingsController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/cs/settings` | `ContinuitySteward/Settings` | `ProfileService::getSettings()` | UC-CS-002 |
| upgradeFromInvited | POST | `/cs/settings/upgrade` | — | `SubscriptionService::upgradeCsToBusiness()` | UC-CS-003 |

---

## `app/Http/Controllers/SupportSteward/`

### `DashboardController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/ss/dashboard` | `SupportSteward/Dashboard` | `SsDashboardService::getStats()` | UC-SS-013 |

### `ProfileController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| edit | GET | `/ss/edit-profile` | `SupportSteward/EditProfile` | `ProfileService::computeCompletion()` | UC-SS-001 |
| update | POST | `/ss/profile` | — | `ProfileService::updateBasic()` | UC-SS-002 |

### `ProvidersController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/ss/providers` | `SupportSteward/Providers` | `StewardService::listForSteward(ss)` | UC-SS-013 |
| notifyUnresponsive | POST | `/ss/providers/{plan}/notify-unresponsive` | — | `StewardService::notifyUnresponsive()` | UC-SS-042 |

### `ContinuityStewardsController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/ss/continuity-stewards` | `SupportSteward/ContinuityStewards` | `StewardService::listCSAcrossSsPlans()` | UC-SS-020 |

### `TasksController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/ss/my-tasks` | `SupportSteward/MyTasks` | `PlanService::tasksForSteward(ss)` | UC-SS-021 |
| complete | POST | `/ss/my-tasks/{task}/complete` | — | `PlanService::completeTask()` | UC-SS-022 |

### `CriticalIncidentController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/ss/critical-incident-log` | `SupportSteward/CriticalIncidentLog` | `IncidentService::listForSteward(ss)` | UC-SS-040 |
| report | POST | `/ss/critical-incident-log/report` | — | `IncidentService::report()` | UC-SS-040 |
| escalate | POST | `/ss/critical-incident-log/{incident}/escalate` | — | `IncidentService::escalate()` | UC-SS-041 |

### `DocumentsController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/ss/important-documents` | `SupportSteward/ImportantDocuments` | `DocumentService::listForSteward()` | UC-SS-070 |
| download | GET | `/ss/important-documents/{doc}/download` | — | `DocumentService::signedUrl()` | UC-SS-070 |

---

## `app/Http/Controllers/BusinessPartner/`

### `DashboardController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/bp/dashboard` | `BusinessPartner/Dashboard` | `BpDashboardService::getStats()` | UC-BP-010 |

### `ProfileController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| edit | GET | `/bp/edit-profile` | `BusinessPartner/EditProfile` | `ProfileService::computeCompletion()` | UC-BP-001 |
| update | POST | `/bp/profile` | — | `ProfileService::updateBasic()` | UC-BP-001 |

### `JobsController.php`
| Method | HTTP | Route | Vue Page | Service | Policy | UC |
|---|---|---|---|---|---|---|
| index | GET | `/bp/find-jobs` | `BusinessPartner/FindJobs` | `BpJobService::listOpen()` | view | UC-BP-020 |
| show | GET | `/bp/find-jobs/{job}` | `BusinessPartner/JobDetail` | `BpJobService::show()` | view | UC-BP-021 |
| save | POST | `/bp/find-jobs/{job}/save` | — | `BpJobService::save()` | save | UC-BP-022 |
| unsave | POST | `/bp/find-jobs/{job}/unsave` | — | `BpJobService::unsave()` | save | UC-BP-023 |

### `ProposalsController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/bp/proposals` | `BusinessPartner/Proposals` | `ProposalService::listForBp()` | UC-BP-033 |
| store | POST | `/bp/proposals` | — | `ProposalService::submit()` | UC-BP-030 |
| update | PATCH | `/bp/proposals/{proposal}` | — | `ProposalService::update()` | UC-BP-031 |
| withdraw | POST | `/bp/proposals/{proposal}/withdraw` | — | `ProposalService::withdraw()` | UC-BP-032 |

### `ContractsController.php`
| Method | HTTP | Route | Vue Page | Service | Policy | UC |
|---|---|---|---|---|---|---|
| index | GET | `/bp/contracts` | `BusinessPartner/Contracts` | `ContractService::listForUser()` | view | UC-BP-040 |
| show | GET | `/bp/contracts/{contract}` | `BusinessPartner/ContractDetail` | `ContractService::show()` | view | UC-BP-040 |
| signByBp | POST | `/bp/contracts/{contract}/sign` | — | `ContractService::signByBp()` | signByBp | UC-BP-042 |
| cancel | POST | `/bp/contracts/{contract}/cancel` | — | `ContractService::cancel()` | cancel | UC-BP-035 |
| reassign | POST | `/bp/contracts/{contract}/reassign` | — | `ContractService::reassign()` | reassign | UC-BP-048 |

### `MilestonesController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/bp/milestones` | `BusinessPartner/Milestones` | `ContractService::milestonesForBp()` | UC-BP-050 |
| submit | POST | `/bp/milestones/{milestone}/submit` | — | `ContractService::submitMilestone()` | UC-BP-051 |

### `InvoicesController.php`
| Method | HTTP | Route | Vue Page | Service | Policy | UC |
|---|---|---|---|---|---|---|
| index | GET | `/bp/invoices` | `BusinessPartner/Invoices` | `InvoiceService::listForBp()` | view | UC-BP-060 |
| store | POST | `/bp/invoices` | — | `InvoiceService::create()` | create | UC-BP-060 |
| addLineItem | POST | `/bp/invoices/{inv}/line-items` | — | `InvoiceService::addLineItem()` | create | UC-BP-061 |
| send | POST | `/bp/invoices/{inv}/send` | — | `InvoiceService::send()` | send | UC-BP-062 |
| void | POST | `/bp/invoices/{inv}/void` | — | `InvoiceService::void()` | void | UC-BP-055 |

### `FinancesController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/bp/finances` | `BusinessPartner/Finances` | `PayoutService::listForBp()` + earnings rollup | UC-BP-070 |

### `PaymentSetupController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/bp/payment-setup` | `BusinessPartner/PaymentSetup` | Stripe Connect state | UC-BP-070 |
| onboard | POST | `/bp/payment-setup/onboard` | — | `PayoutService::startConnectOnboarding()` | UC-BP-070 |
| refreshState | POST | `/bp/payment-setup/refresh` | — | Stripe API state pull | UC-BP-071 |
| uploadTaxDoc | POST | `/bp/payment-setup/tax-docs` | — | `PayoutService::uploadTaxDoc()` | UC-BP-072 |

### `TeamController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/bp/team` | `BusinessPartner/Team` | `TeamService::listMembers()` | UC-BP-080 |
| invite | POST | `/bp/team/invite` | — | `TeamService::invite()` | UC-BP-081 |
| updatePermissions | PATCH | `/bp/team/members/{member}` | — | `TeamService::updatePermissions()` | UC-BP-082 |
| remove | DELETE | `/bp/team/members/{member}` | — | `TeamService::remove()` | UC-BP-083 |

**Notes:** All Team methods enforce `$user->bp_type === 'agency'` server-side (the route is loaded for freelancers but server returns 403).

---

## `app/Http/Controllers/Admin/`

### `DashboardController.php`
| Method | HTTP | Route | Vue Page | Service | Policy | UC |
|---|---|---|---|---|---|---|
| index | GET | `/admin/dashboard` | `Admin/Dashboard` | `AdminDashboardService::getStats()` | accessAdminPortal | UC-ADM-001 |

### `PackagesController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/admin/packages` | `Admin/Packages` | `AdminPackageService::listPackages()` | UC-ADM-040 |
| store | POST | `/admin/packages` | — | `AdminPackageService::createOverride()` | UC-ADM-041 |
| update | PATCH | `/admin/packages/{pkg}` | — | `AdminPackageService::updateOverride()` | UC-ADM-042 |
| expire | POST | `/admin/packages/{pkg}/expire` | — | `AdminPackageService::expireOverride()` | UC-ADM-043 |

### `UsersController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/admin/users` | `Admin/Users` | `AdminUserService::list()` | UC-ADM-020 |
| show | GET | `/admin/users/{user}` | `Admin/UserDetail` | `AdminUserService::view()` | UC-ADM-021 |
| lock | POST | `/admin/users/{user}/lock` | — | `AdminUserService::lock()` | UC-ADM-023 |
| unlock | POST | `/admin/users/{user}/unlock` | — | `AdminUserService::unlock()` | UC-ADM-024 |
| forceReset | POST | `/admin/users/{user}/force-reset` | — | `AdminUserService::forcePasswordReset()` | UC-ADM-025 |
| changeRole | POST | `/admin/users/{user}/role` | — | `AdminUserService::changeRole()` | UC-ADM-026 |
| deactivate | POST | `/admin/users/{user}/deactivate` | — | `AdminUserService::deactivate()` | UC-ADM-027 |
| reactivate | POST | `/admin/users/{user}/reactivate` | — | `AdminUserService::reactivate()` | UC-ADM-028 |
| impersonate | POST | `/admin/users/{user}/impersonate` | — | `AdminUserService::impersonate()` | UC-ADM-022 |

### `RolesController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/admin/roles` | `Admin/Roles` | `AdminRoleService::listRoles()` | UC-ADM-050 |
| store | POST | `/admin/roles` | — | `AdminRoleService::createRole()` | UC-ADM-051 |
| update | PATCH | `/admin/roles/{role}` | — | `AdminRoleService::updatePermissions()` | UC-ADM-052 |
| destroy | DELETE | `/admin/roles/{role}` | — | `AdminRoleService::delete()` | UC-ADM-053 |
| assign | POST | `/admin/roles/{role}/assign` | — | `AdminRoleService::assignToUser()` | UC-ADM-054 |

### `PaymentsController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/admin/payments` | `Admin/Payments` | `AdminPaymentService::listTransactions()` + `stripeSnapshot()` | UC-ADM-070 |
| show | GET | `/admin/payments/{payment}` | `Admin/PaymentDetail` | `AdminPaymentService::viewTransaction()` | UC-ADM-071 |
| refund | POST | `/admin/payments/{payment}/refund` | — | `AdminPaymentService::refund()` | UC-ADM-072 |
| webhookEvents | GET | `/admin/payments/webhooks` | `Admin/WebhookEvents` | `AdminPaymentService::listWebhookEvents()` | UC-ADM-046 |

### `ComplaintsController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/admin/complaints` | `Admin/Complaints` | `AdminComplaintService::list()` | UC-ADM-060 |
| show | GET | `/admin/complaints/{complaint}` | `Admin/ComplaintDetail` | `AdminComplaintService::view()` | UC-ADM-051 |
| assign | POST | `/admin/complaints/{complaint}/assign` | — | `AdminComplaintService::assign()` | UC-ADM-052 |
| changeStatus | POST | `/admin/complaints/{complaint}/status` | — | `AdminComplaintService::changeStatus()` | UC-ADM-055 |
| changePriority | POST | `/admin/complaints/{complaint}/priority` | — | `AdminComplaintService::changePriority()` | UC-ADM-064 |
| reply | POST | `/admin/complaints/{complaint}/reply` | — | `AdminComplaintService::reply()` | UC-ADM-053 |
| resolve | POST | `/admin/complaints/{complaint}/resolve` | — | `AdminComplaintService::resolve()` | UC-ADM-055 |
| close | POST | `/admin/complaints/{complaint}/close` | — | `AdminComplaintService::close()` | UC-ADM-055 |

---

## `app/Http/Controllers/Shared/`

### `OverviewController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/{portal}/overview` *(portal = provider\|cs\|ss\|bp)* | `shared/Overview` | `OverviewService::getBundle()` | UC-XP-005 |

### `MessagesController.php`
| Method | HTTP | Route | Vue Page | Service | Policy | UC |
|---|---|---|---|---|---|---|
| index | GET | `/{portal}/messages` | `shared/Messages` | `MessagingService::listThreads()` | view | UC-PRV-160 |
| show | GET | `/{portal}/messages/{thread}` | `shared/Messages` | `MessagingService::getThread()` | view | UC-PRV-160 |
| store | POST | `/{portal}/messages/{thread}` | — | `MessagingService::send()` | send | UC-PRV-161 |
| createThread | POST | `/{portal}/messages` | — | `MessagingService::createThread()` | createThread | UC-PRV-160 |
| markRead | POST | `/{portal}/messages/{thread}/read` | — | `MessagingService::markRead()` | view | UC-PRV-162 |
| archive | POST | `/{portal}/messages/{thread}/archive` | — | `MessagingService::archive()` | archive | UC-PRV-170 |

### `ActivityController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/{portal}/activity` | `shared/Activity` | `ActivityService::getFiltered()` | UC-PRV-090 |
| markRead | POST | `/{portal}/activity/{event}/read` | — | `ActivityService::markRead()` | UC-PRV-092 |
| markAllRead | POST | `/{portal}/activity/mark-all-read` | — | `ActivityService::markAllRead()` | UC-PRV-093 |

### `SupportController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| index | GET | `/{portal}/support` | `shared/Support` | `SupportService::getHelpArticles()` + user's own tickets | UC-XP-043 |
| createTicket | POST | `/{portal}/support/tickets` | — | `SupportService::createTicket()` | UC-XP-040 |
| submitFeedback | POST | `/{portal}/support/feedback` | — | `SupportService::submitFeedback()` | UC-XP-040 |
| reply | POST | `/{portal}/support/tickets/{complaint}/reply` | — | `SupportService::reply()` | UC-XP-041 |

---

## `app/Http/Controllers/Public/`

### `ProfileController.php`
| Method | HTTP | Route | Vue Page | Service | UC |
|---|---|---|---|---|---|
| showProvider | GET | `/public/provider` (query: `?slug=`) | `public/ProviderProfile` | `ProfileService::publicProfileBySlug('provider')` | UC-PRV-018 |
| showCs | GET | `/public/continuity-steward` | `public/ContinuityStewardProfile` | `ProfileService::publicProfileBySlug('cs')` | UC-CS-003 |
| showSs | GET | `/public/support-steward` | `public/SupportStewardProfile` | `ProfileService::publicProfileBySlug('ss')` | UC-SS-003 |
| showBusiness | GET | `/public/business` | `public/BusinessProfile` | `ProfileService::publicProfileBySlug('bp')` | UC-BP-002 |

---


---

## Additional Controllers + Methods (added in corrected version)

These extend Section 8 with the controller routes/methods required by the 117 derived UCs in `AEGIS_USE_CASES_ADDITIONS.md`. For controllers already documented above, only the new methods are listed.

### Provider portal — new + extended methods

#### `app/Http/Controllers/Provider/RosterController.php` *(new)*
**Policy:** `VaultPolicy`

| Method | HTTP | Route | Inertia Page | Service Call | UC |
|---|---|---|---|---|---|
| index | GET | `/provider/roster` | `Provider/Roster` | `VaultService::getRoster()` | UC-PRV-072 |
| store | POST | `/provider/roster` | — | `VaultService::upsertRosterEntry()` | UC-PRV-072 |
| update | PATCH | `/provider/roster/{item}` | — | `VaultService::upsertRosterEntry()` | UC-PRV-072 |
| discharge | POST | `/provider/roster/{item}/discharge` | — | `VaultService::dischargeClient()` | UC-PRV-073 |

#### `Provider/ContinuityStewardController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| designatePrimarySs | POST | `/provider/support-stewards/primary` | `StewardService::inviteExisting()` | UC-PRV-060 |
| designateAlternateSs | POST | `/provider/support-stewards/alternate` | `StewardService::inviteExisting()` | UC-PRV-061 |
| copyTasks | POST | `/provider/support-stewards/{designation}/copy-tasks` | `StewardService::copyTasksFromPrimary()` | UC-PRV-062 |
| removeSs | DELETE | `/provider/support-stewards/{designation}` | `StewardService::remove()` | UC-PRV-063 |
| resendInvite | POST | `/provider/continuity-stewards/{designation}/resend` | `StewardService::resendInvite()` | UC-PRV-206 |
| setPermissions | PATCH | `/provider/continuity-stewards/{designation}/permissions` | `VaultService::setPermissions()` | UC-PRV-204 |
| setPaymentModel | PATCH | `/provider/continuity-stewards/{designation}/payment-model` | `StewardService::setPaymentModel()` | UC-PRV-146 |

#### `Provider/DocumentsController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| uploadLibrary | POST | `/provider/documents/library` | `DocumentService::uploadLibrary()` | UC-PRV-081 |
| requestFromSteward | POST | `/provider/documents/request` | `DocumentService::requestFromSteward()` | UC-PRV-082 |
| sendForSignature | POST | `/provider/documents/{doc}/send-signature` | `DocumentService::sendForSignature()` | UC-PRV-190 |
| sign | POST | `/provider/documents/{doc}/sign` | `DocumentService::addSignature()` | UC-PRV-191 |
| amend | POST | `/provider/documents/{doc}/amend` | `DocumentService::amend()` | UC-PRV-192 |
| renew | POST | `/provider/documents/{doc}/renew` | `DocumentService::renew()` | UC-PRV-193 |
| archive | POST | `/provider/documents/{doc}/archive` | `DocumentService::archive()` | UC-PRV-194 |
| deleteDraft | DELETE | `/provider/documents/{doc}` | `DocumentService::delete()` | UC-PRV-195 |
| requestRelease | POST | `/provider/documents/{doc}/release-request` | `DocumentService::requestRelease()` | UC-PRV-196 |
| sendReminder | POST | `/provider/documents/{doc}/reminder` | `DocumentService::sendReminder()` | UC-PRV-197 |

#### `Provider/VaultController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| update | PATCH | `/provider/vault/items/{item}` | `VaultService::update()` | UC-PRV-198 |
| delete | DELETE | `/provider/vault/items/{item}` | `VaultService::delete()` | UC-PRV-199 |

#### `Provider/NetworkController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| browseBPs | GET | `/provider/network/business-partners` | `NetworkService::listConnections(type=business_partner)` | UC-PRV-107 |
| sendReferralFromNetwork | POST | `/provider/network/{user}/refer` | `ReferralService::send()` | UC-PRV-108 |
| quickEditSpecialties | PATCH | `/provider/network/specialties` | `ProfileService::updateSpecialties()` | UC-PRV-207 |

#### `Provider/ReferralsController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| cancel | POST | `/provider/referrals/{ref}/cancel` | `ReferralService::cancel()` | UC-PRV-110 |
| respond | POST | `/provider/referrals/{ref}/respond` | `ReferralService::accept` or `decline` | UC-PRV-111 |

#### `Provider/ServicesController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| togglePublic | POST | `/provider/services/{svc}/toggle-public` | `ServiceService::togglePublic()` | UC-PRV-127 |

#### `Provider/JobPostingsController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| store | POST | `/provider/job-postings` | `BpJobService::create()` | UC-PRV-130 |
| close | POST | `/provider/job-postings/{job}/close` | `BpJobService::close()` | UC-PRV-134 |

#### `Provider/FinancesController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| payInvoice | POST | `/provider/finances/invoices/{inv}/pay` | `InvoiceService::approve()` | UC-PRV-136 |
| signContract | POST | `/provider/finances/contracts/{ctr}/sign` | `ContractService::signByPractitioner()` | UC-PRV-137 |
| cancelContract | POST | `/provider/finances/contracts/{ctr}/cancel` | `ContractService::cancel()` | UC-PRV-138 |
| approveMilestone | POST | `/provider/finances/contracts/{ctr}/milestones/{ms}/approve` | `ContractService::approveMilestone()` | UC-PRV-135 |
| overrideInvoiceStatus | POST | `/provider/finances/invoices/{inv}/status` | `InvoiceService::overrideStatus()` | UC-PRV-208 `[admin only]` |

#### `Provider/SettingsController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| addPaymentMethod | POST | `/provider/settings/billing/payment-methods` | `SubscriptionService::addPaymentMethod()` | UC-PRV-141 |
| setAutopay | POST | `/provider/settings/billing/autopay` | `SubscriptionService::setAutopay()` | UC-PRV-144 |
| cancelSubscription | POST | `/provider/settings/billing/cancel` | `SubscriptionService::cancel()` | UC-PRV-145 |
| changePassword | POST | `/provider/settings/security/password` | `AuthService::changePassword()` | UC-PRV-171 |
| toggleMfa | POST | `/provider/settings/security/mfa` | `AuthService::toggleMfa()` | UC-PRV-171 |
| exportData | POST | `/provider/settings/data/export` | `ProfileService::exportUserData()` | UC-PRV-173 |
| revokeSession | DELETE | `/provider/settings/security/sessions/{sess}` | `AuthService::revokeSession()` | UC-PRV-174 |
| updateServicesOffered | POST | `/provider/settings/profile/services` | `ProfileService::updateServicesOffered()` | UC-PRV-013 |
| setIntentSegment | POST | `/provider/settings/intent` | `ProfileService::setIntentSegment()` | UC-PRV-215 |

#### `Provider/ProfileController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| addEducation | POST | `/provider/profile/education` | `ProfileService::updateCredentials()` (extended) | UC-PRV-020 |

### Continuity Steward portal

#### `ContinuitySteward/InvoicesController.php` *(new)*
**Policy:** `BpInvoicePolicy`

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| index | GET | `/cs/invoices` | `InvoiceService::listForCs()` | UC-CS-080 |
| store | POST | `/cs/invoices` | `InvoiceService::createCsInvoice()` | UC-CS-085 |
| send | POST | `/cs/invoices/{inv}/send` | `InvoiceService::sendCsInvoice()` | UC-CS-081 |
| sendReminder | POST | `/cs/invoices/{inv}/remind` | `InvoiceService::sendReminder()` | UC-CS-082 |
| markPaid | POST | `/cs/invoices/{inv}/mark-paid` | `InvoiceService::markPaidManually()` | UC-CS-083 |
| void | POST | `/cs/invoices/{inv}/void` | `InvoiceService::voidCsInvoice()` | UC-CS-084 |
| copyStripeId | POST | `/cs/invoices/{inv}/copy-stripe-id` | (read-only) | UC-CS-089 |

#### `ContinuitySteward/IncidentsController.php` *(new)*

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| index | GET | `/cs/incidents` | `IncidentService::listForSteward()` | UC-CS-040 |
| workTasks | POST | `/cs/incidents/{inc}/tasks/{task}/complete` | `IncidentService::completeTask()` | UC-CS-044 |
| escalateAegis | POST | `/cs/incidents/{inc}/escalate-aegis` | `IncidentService::escalateToAegis()` | UC-CS-045 |
| addUpdate | POST | `/cs/incidents/{inc}/updates` | `IncidentService::addUpdate()` | UC-CS-046 |
| attachDoc | POST | `/cs/incidents/{inc}/attachments` | `IncidentService::attachDocument()` | UC-CS-047 |
| close | POST | `/cs/incidents/{inc}/close` | `IncidentService::close()` | UC-CS-048 |
| reopen | POST | `/cs/incidents/{inc}/reopen` | `IncidentService::reopen()` | UC-CS-049 |

#### `ContinuitySteward/VaultController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| download | GET | `/cs/vault/items/{item}/download` | `VaultService::signedUrl()` | UC-CS-062 |
| reveal | POST | `/cs/vault/items/{item}/reveal` | `VaultService::reveal()` | UC-CS-063 |
| referDuringIncident | POST | `/cs/vault/roster/{item}/refer` | `ReferralService::sendDuringIncident()` | UC-CS-065 |
| exportAuditLog | GET | `/cs/vault/audit-log/export` | `VaultService::exportAuditLog()` | UC-CS-066 |

#### `ContinuitySteward/ProvidersController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| requestRoleChange | POST | `/cs/providers/{designation}/role-change` | `StewardService::requestRoleChange()` | UC-CS-023 |
| resign | POST | `/cs/providers/{designation}/resign` | `StewardService::resign()` | UC-CS-026 |
| reAttest | POST | `/cs/providers/{designation}/re-attest` | `StewardService::reAttestAnnual()` | UC-CS-075 |
| certify | POST | `/cs/providers/{designation}/certify` | `StewardService::certify()` | UC-CS-110 |
| sendReferral | POST | `/cs/providers/{practitioner}/refer` | `ReferralService::send()` | UC-CS-111 |

#### `ContinuitySteward/TasksController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| complete | POST | `/cs/tasks/{task}/complete` | `PlanService::completeTask()` | UC-CS-031 |
| addNote | POST | `/cs/tasks/{task}/note` | `PlanService::addTaskNote()` | UC-CS-032 |
| requestExtension | POST | `/cs/tasks/{task}/extension` | `PlanService::requestTaskExtension()` | UC-CS-033 |
| flag | POST | `/cs/tasks/{task}/flag` | `PlanService::flagTaskException()` | UC-CS-034 |
| clearException | POST | `/cs/tasks/{task}/clear-exception` | `PlanService::clearTaskException()` | UC-CS-036 |
| annualReattest | POST | `/cs/tasks/annual-reattest` | `StewardService::countersignAnnualReview()` | UC-CS-035 |

#### `ContinuitySteward/DocumentsController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| upload | POST | `/cs/documents` | `DocumentService::uploadByStreward()` | UC-CS-071 |
| countersign | POST | `/cs/documents/{doc}/countersign` | `DocumentService::addSignature()` | UC-CS-073 |
| decline | POST | `/cs/documents/{doc}/decline` | `DocumentService::declineSignature()` | UC-CS-074 |

#### `ContinuitySteward/FinancesController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| connectStripe | POST | `/cs/finances/connect-stripe` | `PayoutService::startConnectOnboarding()` | UC-CS-086 |
| setPaymentModel | PATCH | `/cs/finances/payment-model` | `StewardService::setPaymentModel()` | UC-CS-088 |

#### `ContinuitySteward/SettingsController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| updateNotifications | POST | `/cs/settings/notifications` | `NotificationService::setGate()` | UC-CS-092 |
| changePassword | POST | `/cs/settings/security/password` | `AuthService::changePassword()` | UC-CS-093 |
| revokeAllSessions | POST | `/cs/settings/security/revoke-all` | `AuthService::revokeAllSessions()` | UC-CS-094 |
| exportData | POST | `/cs/settings/data/export` | `ProfileService::exportUserData()` | UC-CS-095 |
| pause | POST | `/cs/settings/pause` | `StewardService::pauseStewardship()` | UC-CS-096 |
| closeAccount | POST | `/cs/settings/close-account` | `AuthService::closeAccount()` | UC-CS-097 |
| updateVisibility | POST | `/cs/settings/visibility` | `ProfileService::updateVisibility()` | UC-CS-098 |
| upgradeTier | POST | `/cs/settings/upgrade` | `SubscriptionService::upgradeCsToBusiness()` | UC-CS-114 |

#### `ContinuitySteward/SupportController.php` *(new)*

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| index | GET | `/cs/support` | `SupportService::listForUser()` | UC-CS-100 |
| storeTicket | POST | `/cs/support/tickets` | `SupportService::createTicket()` | UC-CS-100 |
| replyTicket | POST | `/cs/support/tickets/{t}/reply` | `SupportService::reply()` | UC-CS-102 |
| closeTicket | POST | `/cs/support/tickets/{t}/close` | `SupportService::closeTicket()` | UC-CS-103 |
| submitFeedback | POST | `/cs/support/feedback` | `SupportService::submitFeedback(channel=feedback_button)` | UC-CS-104 |
| submitFeedbackFab | POST | `/cs/support/feedback-fab` | `SupportService::submitFeedback(channel=contextual_questionnaire)` | UC-CS-105 |
| helpArticles | GET | `/cs/support/help` | `SupportService::getHelpArticles()` | UC-CS-106 |

### Support Steward portal — new methods

#### `SupportSteward/TasksController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| complete | POST | `/ss/tasks/{task}/complete` | `PlanService::completeTask()` | UC-SS-031 |
| addNote | POST | `/ss/tasks/{task}/note` | `PlanService::addTaskNote()` | UC-SS-032 |
| requestExtension | POST | `/ss/tasks/{task}/extension` | `PlanService::requestTaskExtension()` | UC-SS-033 |
| escalateToCs | POST | `/ss/tasks/{task}/escalate-cs` | `PlanService::escalateTaskToCs()` | UC-SS-034 |
| certifyPlan | POST | `/ss/tasks/certify-plan` | `StewardService::certify()` | UC-SS-035 |
| acknowledgePlan | POST | `/ss/tasks/acknowledge` | `StewardService::acknowledgePlan()` | UC-SS-012 |

#### `SupportSteward/CriticalIncidentController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| attachDoc | POST | `/ss/incidents/{inc}/attachments` | `IncidentService::attachDocument()` | UC-SS-043 |
| withdraw | POST | `/ss/incidents/{inc}/withdraw` | `IncidentService::withdraw()` | UC-SS-044 |

#### `SupportSteward/ProvidersController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| addBusinessCs | POST | `/ss/providers/{practitioner}/add-business-cs` | `StewardService::recommendBusinessCS()` | UC-SS-018 |
| saveNote | POST | `/ss/providers/{practitioner}/note` | `StewardService::saveProviderNote()` | UC-SS-019 |
| notifyUnresponsive | POST | `/ss/providers/{practitioner}/notify-unresponsive` | `StewardService::notifyUnresponsive()` | UC-SS-017 |

#### `SupportSteward/DocumentsController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| download | GET | `/ss/documents/{doc}/download` | `DocumentService::signedUrl()` | UC-SS-061 |
| upload | POST | `/ss/documents` | `DocumentService::uploadByStreward()` | UC-SS-062 |

#### `SupportSteward/SettingsController.php` *(new)*

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| updateNotifications | POST | `/ss/settings/notifications` | `NotificationService::setGate()` | UC-SS-073 |
| changePassword | POST | `/ss/settings/security/password` | `AuthService::changePassword()` | UC-SS-074 |
| verifyEmailChange | POST | `/ss/settings/security/verify-email` | `AuthService::verifyEmail()` | UC-SS-075 |
| pause | POST | `/ss/settings/pause` | `StewardService::pauseStewardship()` | UC-SS-076 |
| exportData | POST | `/ss/settings/data/export` | `ProfileService::exportUserData()` | UC-SS-077 |
| closeAccount | POST | `/ss/settings/close-account` | `AuthService::closeAccount()` | UC-SS-078 |
| updateContactVisibility | POST | `/ss/settings/visibility` | `ProfileService::updateVisibility()` | UC-SS-079 |
| applyTheme | POST | `/ss/settings/theme` | `ProfileService::setTheme()` | UC-SS-080 |
| grantDelegate | POST | `/ss/settings/delegate` | `ProfileService::grantEditAuthorization()` | UC-SS-090 |

#### `SupportSteward/SupportController.php` *(new)*

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| storeTicket | POST | `/ss/support/tickets` | `SupportService::createTicket()` | UC-SS-081 |
| replyTicket | POST | `/ss/support/tickets/{t}/reply` | `SupportService::reply()` | UC-SS-083 |
| submitFeedback | POST | `/ss/support/feedback` | `SupportService::submitFeedback(channel=feedback_button)` | UC-SS-084 |
| submitFeedbackFab | POST | `/ss/support/feedback-fab` | `SupportService::submitFeedback(channel=contextual_questionnaire)` | UC-SS-085 |
| helpArticles | GET | `/ss/support/help` | `SupportService::getHelpArticles()` | UC-SS-086 |

### Business Partner portal

#### `BusinessPartner/ProfileController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| completeCompany | POST | `/bp/profile/company` | `ProfileService::updateBasic()` | UC-BP-003 |
| updateCertifications | POST | `/bp/profile/certifications` | `ProfileService::updateMeta('bp_certifications')` | UC-BP-011 |
| updateCoverage | POST | `/bp/profile/coverage` | `ProfileService::updateMeta('bp_state_coverage')` | UC-BP-012 |
| updateSpecializations | POST | `/bp/profile/specializations` | `ProfileService::updateMeta('bp_specializations')` | UC-BP-013 |
| updatePortfolio | POST | `/bp/profile/portfolio` | `ProfileService::updateMeta('bp_portfolio_items')` | UC-BP-014 |
| toggleNetworkVisibility | POST | `/bp/profile/visibility` | `ProfileService::updateVisibility()` | UC-BP-016 |
| toggleRevenuePrivacy | POST | `/bp/profile/revenue-visibility` | `ProfileService::setRevenueVisibility()` | UC-BP-017 |

#### `BusinessPartner/ProposalsController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| store | POST | `/bp/proposals` | `ProposalService::submit()` | UC-BP-025 |
| update | PATCH | `/bp/proposals/{prop}` | `ProposalService::edit()` | UC-BP-026 |
| withdraw | POST | `/bp/proposals/{prop}/withdraw` | `ProposalService::withdraw()` | UC-BP-027 |

#### `BusinessPartner/ContractsController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| cancel | POST | `/bp/contracts/{ctr}/cancel` | `ContractService::cancel()` | UC-BP-035 |
| pause | POST | `/bp/contracts/{ctr}/pause` | `ContractService::pause()` | UC-BP-036 |
| resume | POST | `/bp/contracts/{ctr}/resume` | `ContractService::resume()` | UC-BP-037 |

#### `BusinessPartner/MilestonesController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| store | POST | `/bp/contracts/{ctr}/milestones` | `ContractService::addMilestone()` | UC-BP-039 |
| withdraw | POST | `/bp/milestones/{ms}/withdraw` | `ContractService::withdrawMilestone()` | UC-BP-043 |

#### `BusinessPartner/InvoicesController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| resend | POST | `/bp/invoices/{inv}/resend` | `InvoiceService::resend()` | UC-BP-052 |
| sendReminder | POST | `/bp/invoices/{inv}/remind` | `InvoiceService::sendReminder()` | UC-BP-053 |
| void | POST | `/bp/invoices/{inv}/void` | `InvoiceService::void()` | UC-BP-055 |
| refund | POST | `/bp/invoices/{inv}/refund` | `InvoiceService::refund()` | UC-BP-056 |
| markPaid | POST | `/bp/invoices/{inv}/mark-paid` | `InvoiceService::markPaidManually()` | UC-BP-057 |
| addLineItem | POST | `/bp/invoices/{inv}/lines` | `InvoiceService::addLineItem()` | UC-BP-059 |
| updateDraft | PATCH | `/bp/invoices/{inv}/draft` | `InvoiceService::updateDraft()` | UC-BP-120 |

#### `BusinessPartner/TaxDocumentsController.php` *(new)*

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| index | GET | `/bp/tax-documents` | `TaxDocumentService::listForUser()` | UC-BP-061 |
| download | GET | `/bp/tax-documents/{doc}/download` | `TaxDocumentService::download()` | UC-BP-063 |
| updateAddress | POST | `/bp/tax-documents/address` | `TaxDocumentService::updateTaxAddress()` | UC-BP-073 |
| uploadW9 | POST | `/bp/tax-documents/w9` | `TaxDocumentService::uploadW9()` | UC-BP-074 |

#### `BusinessPartner/PaymentSetupController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| connect | POST | `/bp/payment-setup/connect` | `PayoutService::startConnectOnboarding()` | UC-BP-004 |
| exportStripeData | GET | `/bp/payment-setup/export` | `PayoutService::exportStripeData()` | UC-BP-075 |
| disconnect | POST | `/bp/payment-setup/disconnect` | `PayoutService::disconnect()` | UC-BP-076 |

#### `BusinessPartner/TeamController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| removeMember | DELETE | `/bp/team/members/{m}` | `TeamService::removeMember()` | UC-BP-084 |
| setStatus | PATCH | `/bp/team/members/{m}/status` | `TeamService::setStatus()` | UC-BP-085 |
| acceptInvitation | POST | `/invite/team/{token}` | `TeamService::acceptInvitation()` | UC-BP-086 |

#### `BusinessPartner/MessagesController.php` *(new)* — delegates to `Shared/MessagesController`
#### `BusinessPartner/SettingsController.php` *(new)* — mirrors CS/Settings

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| updateNotifications | POST | `/bp/settings/notifications` | `NotificationService::setGate()` | UC-BP-100 |
| changePassword | POST | `/bp/settings/security/password` | `AuthService::changePassword()` | UC-BP-101 |
| verifyEmailChange | POST | `/bp/settings/security/verify-email` | `AuthService::verifyEmail()` | UC-BP-102 |
| closeAccount | POST | `/bp/settings/close-account` | `AuthService::closeAccount()` | UC-BP-103 |
| pause | POST | `/bp/settings/pause` | `AuthService::pauseAccount()` | UC-BP-104 |
| transfer | POST | `/bp/settings/transfer` | `AuthService::transferAccount()` | UC-BP-105 |
| cancelSubscription | POST | `/bp/settings/cancel-subscription` | `SubscriptionService::cancel()` | UC-BP-106 |
| updateRatesVisibility | POST | `/bp/settings/rates-visibility` | `ProfileService::updateVisibility()` | UC-BP-107 |
| exportData | POST | `/bp/settings/data/export` | `ProfileService::exportUserData()` | UC-BP-108 |
| applyTheme | POST | `/bp/settings/theme` | `ProfileService::setTheme()` | UC-BP-109 |

#### `BusinessPartner/SupportController.php` *(new)* — mirrors CS/Support

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| storeTicket | POST | `/bp/support/tickets` | `SupportService::createTicket()` | UC-BP-110 |
| replyTicket | POST | `/bp/support/tickets/{t}/reply` | `SupportService::reply()` | UC-BP-112 |
| submitFeedback | POST | `/bp/support/feedback` | `SupportService::submitFeedback(channel=feedback_button)` | UC-BP-113 |
| submitFeedbackFab | POST | `/bp/support/feedback-fab` | `SupportService::submitFeedback(channel=contextual_questionnaire)` | UC-BP-114 |
| helpArticles | GET | `/bp/support/help` | `SupportService::getHelpArticles()` | UC-BP-115 |

### Admin portal

#### `Admin/PackagesController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| updatePrice | POST | `/admin/packages/{pkg}/price` | `AdminPackageService::updateOverride()` | UC-ADM-011 |
| toggleFeatureFlag | POST | `/admin/packages/{pkg}/feature-flag` | `AdminPackageService::toggleFeatureFlag()` | UC-ADM-012 |
| setLimits | POST | `/admin/packages/{pkg}/limits` | `AdminPackageService::setLimits()` | UC-ADM-013 |

#### `Admin/UsersController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| impersonate | POST | `/admin/users/{u}/impersonate` | `AdminUserService::impersonate()` | UC-ADM-029 |

#### `Admin/RolesController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| storeCustomRole | POST | `/admin/roles` | `AdminRoleService::createRole()` | UC-ADM-031 |
| setPermissions | POST | `/admin/roles/{r}/permissions` | `AdminRoleService::updatePermissions()` | UC-ADM-032 |
| deleteCustomRole | DELETE | `/admin/roles/{r}` | `AdminRoleService::delete()` | UC-ADM-033 |

#### `Admin/PayoutsController.php` *(new)*

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| index | GET | `/admin/payouts/pending` | `AdminPayoutService::listPending()` | UC-ADM-044 |
| releaseManually | POST | `/admin/payouts/{p}/release` | `AdminPayoutService::releaseManually()` | UC-ADM-045 |

#### `Admin/HelpArticlesController.php` *(new)*

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| index | GET | `/admin/help-articles` | `AdminHelpArticleService::list()` | UC-ADM-058 |
| store | POST | `/admin/help-articles` | `AdminHelpArticleService::upsert()` | UC-ADM-058 |
| update | PATCH | `/admin/help-articles/{a}` | `AdminHelpArticleService::upsert()` | UC-ADM-058 |
| publish | POST | `/admin/help-articles/{a}/publish` | `AdminHelpArticleService::publish()` | UC-ADM-059 |
| reorder | POST | `/admin/help-articles/reorder` | `AdminHelpArticleService::reorder()` | UC-ADM-060 |

#### `Admin/ComplaintsController.php` — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| changeStatus | POST | `/admin/complaints/{c}/status` | `AdminComplaintService::changeStatus()` | UC-ADM-055 |
| escalate | POST | `/admin/complaints/{c}/escalate` | `AdminComplaintService::escalate()` | UC-ADM-056 |

### Auth — new controllers

#### `Auth/OnboardingController.php` *(new)*

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| showRolePicker | GET | `/onboarding/role` | (page only) | UC-PRV-212 |
| storeRole | POST | `/onboarding/role` | `AuthService::startOnboarding()` | UC-PRV-212 |
| showIntent | GET | `/onboarding/intent` | (page only) | UC-PRV-215 |
| storeIntent | POST | `/onboarding/intent` | `ProfileService::setIntentSegment()` | UC-PRV-215 |
| selectMaatAddon | POST | `/onboarding/maat-addon` | `SubscriptionService::toggleMaatAddon()` | UC-PRV-209 |

#### `Auth/VerifyEmailController.php` *(new)*

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| verify | GET | `/verify-email/{token}` | `AuthService::verifyEmail()` | UC-PRV-213 |
| resend | POST | `/verify-email/resend` | `AuthService::resendVerificationEmail()` | UC-PRV-213 |

### Shared/MessagesController — new methods

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| pinThread | POST | `/{portal}/messages/threads/{t}/pin` | `MessagingService::togglePin()` | UC-PRV-163 |
| muteThread | POST | `/{portal}/messages/threads/{t}/mute` | `MessagingService::toggleMute()` | UC-PRV-163 |
| markUnread | POST | `/{portal}/messages/threads/{t}/unread` | `MessagingService::markUnread()` | UC-PRV-164 |



### `app/Http/Controllers/Provider/NewsController.php` *(new — UC-PRV-240..248)*
**Policy:** `NewsPolicy` (post.author or admin for write actions)

| Method | HTTP | Route | Inertia Page | Service Call | UC |
|---|---|---|---|---|---|
| index | GET | `/provider/news` | `Provider/News` | `NewsService::listFeed()` | UC-PRV-240 |
| storePost | POST | `/provider/news/posts` | — | `NewsService::publishPost()` | UC-PRV-241 |
| updatePost | PATCH | `/provider/news/posts/{post}` | — | `NewsService::editPost()` | UC-PRV-241 |
| deletePost | DELETE | `/provider/news/posts/{post}` | — | `NewsService::deletePost()` | UC-PRV-241 |
| comment | POST | `/provider/news/posts/{post}/comments` | — | `NewsService::comment()` | UC-PRV-242 |
| deleteComment | DELETE | `/provider/news/comments/{comment}` | — | `NewsService::deleteComment()` | UC-PRV-242 |
| react | POST | `/provider/news/posts/{post}/reactions` | — | `NewsService::react()` | UC-PRV-243 |
| votePoll | POST | `/provider/news/posts/{post}/poll-vote` | — | `NewsService::voteInPoll()` | UC-PRV-244 |
| events | GET | `/provider/events` | `Provider/Events` | `NewsService::listEvents()` | UC-PRV-245 |
| rsvp | POST | `/provider/events/{event}/rsvp` | — | `NewsService::rsvpEvent()` | UC-PRV-246 |
| library | GET | `/provider/news/library` | `Provider/NewsLibrary` | `NewsService::browseLibrary()` | UC-PRV-247 |
| trending | GET | `/provider/news/trending` | — (widget) | `NewsService::trending()` | UC-PRV-248 |

### Cross-portal Activity read controllers — new methods on `Shared/ActivityController`

| Method | HTTP | Route | Service Call | UC |
|---|---|---|---|---|
| markRead | POST | `/{portal}/activity/{event}/read` | `ActivityService::markRead()` | UC-PRV-092 (+ CS/SS/BP equivalents) |
| markAllRead | POST | `/{portal}/activity/mark-all-read` | `ActivityService::markAllRead()` | UC-PRV-093 |

### Admin/IncidentsController — new (UC-ADM-068)

| Method | HTTP | Route | Inertia Page | Service Call | UC |
|---|---|---|---|---|---|
| index | GET | `/admin/incidents` | `Admin/Incidents` | `IncidentService::listForAdmin()` | UC-ADM-068 |

### Public/ProfileController — new SS profile route (UC-SS-003)

| Method | HTTP | Route | Inertia Page | Service Call | UC |
|---|---|---|---|---|---|
| showSs | GET | `/profile/ss/{slug}` | `Public/SupportStewardProfile` | `ProfileService::publicSsProfile()` | UC-SS-003 |


# Section 9 — FormRequests

All under `app/Http/Requests/`. Every request implements `authorize()` (delegates to Policy where applicable) and `rules()`. Validation messages live in `lang/en/validation.php`.

---

## Auth

### `Auth/LoginRequest.php`
```php
rules(): array {
    return [
        'email'    => ['required', 'email', 'max:191'],
        'password' => ['required', 'string'],
        'remember' => ['nullable', 'boolean'],
    ];
}
```

### `Auth/RegisterRequest.php`
```php
rules(): array {
    return [
        'display_name' => ['required', 'string', 'max:191'],
        'email'        => ['required', 'email', 'max:191', 'unique:users,email'],
        'password'     => ['required', Password::min(12)->letters()->numbers()->mixedCase(), 'confirmed'],
        'role'         => ['required', new Enum(UserRole::class)],
        'invite_token' => ['nullable', 'string', 'exists:plan_stewards,invite_token'],
        'terms'        => ['accepted'],
    ];
}
```

### `Auth/PasswordResetRequest.php`
```php
rules(): array {
    return [
        'token'    => ['required', 'string', 'size:128'],
        'email'    => ['required', 'email'],
        'password' => ['required', Password::min(12)->letters()->numbers()->mixedCase(), 'confirmed'],
    ];
}
```

---

## Plan

### `Plan/CreatePlanRequest.php`
```php
rules(): array {
    return [
        'template_key' => ['nullable', 'string', 'in:standard,minimal,custom'],
    ];
}
```

### `Plan/SignPlanRequest.php`
```php
rules(): array {
    return [
        'signature_name'  => ['required', 'string', 'max:191'],
        'signature_title' => ['required', 'string', 'max:191'],
        'acknowledgement' => ['accepted'],
    ];
}
```

### `Plan/AttestVaultRequest.php`
```php
rules(): array {
    return [
        'attestation_note' => ['required', 'string', 'max:2000'],
        'acknowledgement'  => ['accepted'],
    ];
}
```

### `Plan/AnnualReviewRequest.php`
```php
rules(): array {
    return [
        'checklist'    => ['required', 'array', 'size:8'],
        'checklist.*'  => ['accepted'],
        'notes'        => ['nullable', 'string', 'max:2000'],
    ];
}
```

---

## Steward

### `Steward/DesignateStewardRequest.php`
```php
rules(): array {
    return [
        'steward_type' => ['required', new Enum(StewardType::class)],
        'role'         => ['required', new Enum(StewardRole::class)],
        'mode'         => ['required', 'in:existing,external'],
        'user_id'      => ['required_if:mode,existing', 'exists:users,id'],
        'email'        => ['required_if:mode,external', 'email', 'max:191'],
        'name'         => ['required_if:mode,external', 'string', 'max:191'],
    ];
}
```

### `Steward/UpdateStewardTaskRequest.php`
```php
rules(): array {
    return [
        'status'         => ['required', new Enum(TaskStatus::class)],
        'exception_note' => ['required_if:status,exception', 'string', 'max:2000'],
    ];
}
```

---

## Incident

### `Incident/ReportIncidentRequest.php`
```php
rules(): array {
    return [
        'plan_id'         => ['required', 'exists:continuity_plans,id'],
        'incident_type'   => ['required', new Enum(IncidentType::class)],
        'severity'        => ['required', new Enum(ActivitySeverity::class)],
        'reported_details'=> ['required', 'string', 'max:5000'],
        'documentation'   => ['nullable', 'array'],
        'documentation.*' => ['file', 'mimes:pdf,jpg,png', 'max:10240'],
    ];
}
```

### `Incident/VerifyIncidentRequest.php`
```php
rules(): array {
    return [
        'verification_note' => ['required', 'string', 'max:2000'],
        'documentation_ok'  => ['accepted'],
    ];
}
```

---

## Vault

### `Vault/UploadVaultItemRequest.php`
```php
rules(): array {
    return [
        'zone'                       => ['required', new Enum(VaultZone::class)],
        'title'                      => ['required', 'string', 'max:191'],
        'category'                   => ['nullable', 'string', 'max:191'],
        'status'                     => ['nullable', new Enum(VaultItemStatus::class)],
        // Credentials zone
        'credential_username'        => ['required_if:zone,credentials', 'string', 'max:191'],
        'credential_password'        => ['required_if:zone,credentials', 'string', 'max:500'],
        'credential_url'             => ['nullable', 'url', 'max:500'],
        // Documents zone
        'document_file'              => ['required_if:zone,documents', 'file', 'mimes:pdf,docx,jpg,png', 'max:25600'],
        // Roster zone
        'client_name'                => ['required_if:zone,roster', 'string', 'max:191'],
        'client_phone'               => ['nullable', 'string', 'max:40'],
        'client_priority'            => ['nullable', 'integer', 'between:1,5'],
        'client_notes'               => ['nullable', 'string', 'max:2000'],
    ];
}
```

### `Vault/SetVaultPermissionsRequest.php`
```php
rules(): array {
    return [
        'permissions'                          => ['required', 'array'],
        'permissions.*.designation_id'         => ['required', 'exists:plan_stewards,id'],
        'permissions.*.vault_access'           => ['required', 'in:none,metadata,scoped,full'],
    ];
}
```

---

## Profile

### `Profile/UpdateBasicProfileRequest.php`
```php
rules(): array {
    return [
        'display_name'    => ['required', 'string', 'max:191'],
        'phone'           => ['nullable', 'string', 'max:40'],
        'location'        => ['nullable', 'string', 'max:191'],
        'organization'    => ['nullable', 'string', 'max:191'],
        'title'           => ['nullable', 'string', 'max:191'],
        'bio'             => ['nullable', 'string', 'max:5000'],
        'avatar_initials' => ['nullable', 'string', 'max:4'],
    ];
}
```

### `Profile/UpdateCredentialsRequest.php`
```php
rules(): array {
    return [
        'credentials'    => ['nullable', 'string', 'max:191'],
        'specialty'      => ['nullable', 'string', 'max:191'],
        'license_state'  => ['nullable', 'string', 'size:2'],
        'license_number' => ['nullable', 'string', 'max:64'],
        'npi'            => ['nullable', 'regex:/^\d{10}$/'],
    ];
}
```

### `Profile/UpdateFeesRequest.php`
```php
rules(): array {
    return [
        'fee_min_cents' => ['nullable', 'integer', 'min:0'],
        'fee_max_cents' => ['nullable', 'integer', 'min:0', 'gte:fee_min_cents'],
        'fee_notes'     => ['nullable', 'string', 'max:1000'],
        'insurance'     => ['nullable', 'array'],
        'insurance.*'   => ['string', 'max:191'],
    ];
}
```

---

## Business

### `Business/CreateJobRequest.php`
```php
rules(): array {
    return [
        'title'              => ['required', 'string', 'max:191'],
        'description'        => ['required', 'string', 'max:10000'],
        'budget_type'        => ['required', 'in:fixed,hourly,retainer'],
        'budget_min_cents'   => ['required', 'integer', 'min:0'],
        'budget_max_cents'   => ['nullable', 'integer', 'gte:budget_min_cents'],
        'engagement_type'    => ['required', 'in:one_off,ongoing,retainer'],
        'urgency'            => ['nullable', 'in:low,normal,high,urgent'],
        'tags'               => ['nullable', 'array', 'max:10'],
        'tags.*'             => ['string', 'max:50'],
        'requirements'       => ['nullable', 'array'],
        'closes_at'          => ['nullable', 'date', 'after:today'],
    ];
}
```

### `Business/SubmitProposalRequest.php`
```php
rules(): array {
    return [
        'job_id'                 => ['required', 'exists:bp_jobs,id'],
        'cover_letter'           => ['required', 'string', 'max:5000'],
        'proposed_rate_type'     => ['required', 'in:fixed,hourly,retainer'],
        'proposed_rate_cents'    => ['required', 'integer', 'min:0'],
        'estimated_hours'        => ['nullable', 'integer', 'min:1'],
        'estimated_completion'   => ['nullable', 'date', 'after:today'],
        'attachments'            => ['nullable', 'array', 'max:5'],
        'attachments.*'          => ['file', 'mimes:pdf,docx,jpg,png', 'max:10240'],
    ];
}
```

### `Business/CreateInvoiceRequest.php`
```php
rules(): array {
    return [
        'contract_id'           => ['required', 'exists:bp_contracts,id'],
        'due_at'                => ['required', 'date', 'after:today'],
        'line_items'            => ['required', 'array', 'min:1'],
        'line_items.*.description' => ['required', 'string', 'max:500'],
        'line_items.*.quantity'    => ['required', 'numeric', 'min:0.01'],
        'line_items.*.unit_price_cents' => ['required', 'integer', 'min:0'],
        'tax_cents'             => ['nullable', 'integer', 'min:0'],
        'notes'                 => ['nullable', 'string', 'max:2000'],
    ];
}
```

### `Business/CreateContractRequest.php`
```php
rules(): array {
    return [
        'proposal_id'          => ['required', 'exists:bp_proposals,id'],
        'milestones'           => ['nullable', 'array'],
        'milestones.*.title'   => ['required', 'string', 'max:191'],
        'milestones.*.amount_cents' => ['required', 'integer', 'min:0'],
        'milestones.*.due_at'  => ['required', 'date'],
        'total_value_cents'    => ['required', 'integer', 'min:0'],
        'scope'                => ['required', 'string', 'max:10000'],
    ];
}
```

---

## Support

### `Support/CreateTicketRequest.php`
```php
rules(): array {
    return [
        'subject'  => ['required', 'string', 'max:191'],
        'body'     => ['required', 'string', 'max:5000'],
        'category' => ['required', 'in:support_ticket,complaint'],
        'priority' => ['nullable', 'in:low,normal,high,urgent'],
        'attachments' => ['nullable', 'array', 'max:3'],
        'attachments.*' => ['file', 'mimes:pdf,jpg,png', 'max:5120'],
    ];
}
```

### `Support/SubmitFeedbackRequest.php`
```php
rules(): array {
    return [
        'channel'        => ['required', new Enum(SubmissionChannel::class)],
        'context'        => ['nullable', 'string', 'max:191'],     // e.g. 'after_plan_sign'
        'rating'         => ['nullable', 'integer', 'between:1,5'],
        'body'           => ['required', 'string', 'max:5000'],
        'allow_followup' => ['nullable', 'boolean'],
    ];
}
```

---

## Admin

### `Admin/LockUserRequest.php`
```php
rules(): array {
    return [
        'reason' => ['required', 'string', 'max:500'],
        'notify' => ['nullable', 'boolean'],
    ];
}
```

### `Admin/ChangeRoleRequest.php`
```php
rules(): array {
    return [
        'role'       => ['required', new Enum(UserRole::class)],
        'audit_note' => ['required', 'string', 'max:500'],
    ];
}
```

### `Admin/RefundPaymentRequest.php`
```php
rules(): array {
    return [
        'amount_cents' => ['required', 'integer', 'min:1'],
        'reason'       => ['required', 'string', 'max:500'],
        'notify'       => ['nullable', 'boolean'],
    ];
}
```

### `Admin/UpdatePackageRequest.php`
```php
rules(): array {
    return [
        'tier_data'    => ['required', 'array'],
        'effective_at' => ['nullable', 'date'],
        'expires_at'   => ['nullable', 'date', 'after:effective_at'],
        'audit_note'   => ['required', 'string', 'max:500'],
    ];
}
```

---


---

## Additional FormRequests (added in corrected version)

Concise rules() arrays. Each request also has its `authorize()` returning the corresponding Policy gate.

### `Auth/OnboardingIntentRequest.php`
- `segment` → required|in:returning_provider,first_time_owner,exploring,burned_out,other

### `Auth/VerifyEmailRequest.php`
- `token` → required|string|size:64

### `Plan/AddPlanTaskRequest.php`
- `title` → required|string|max:191
- `due_date` → nullable|date
- `assigned_to` → nullable|in:cs,ss,practitioner
- `module` → required|in:plan_signing,vault_attestation,annual_review,client_communications

### `Plan/ConfigureIncidentsRequest.php`
- `incident_type` → required|in:death,disability,emergency_unavailability,permanent_unavailability,planned_absence
- `docs_required` → array
- `authorized_cs_ids` → array
- `authorized_ss_ids` → array

### `Steward/AddCheckinRequest.php`
- `practitioner_id` → required|uuid|exists:users,id
- `notes` → required|string|max:5000

### `Steward/CertifyPlanRequest.php`
- `acknowledgment` → required|accepted

### `Steward/ResignStewardRequest.php`
- `reason` → required|string|max:1000

### `Incident/EscalateIncidentRequest.php`
- `reason` → required|string|max:1000
- `target` → required|in:aegis,senior_review

### `Incident/IncidentUpdateRequest.php`
- `body` → required|string|max:5000

### `Incident/AttachIncidentDocRequest.php`
- `file` → required|file|max:25600|mimes:pdf,jpg,png,docx

### `Vault/UpdateVaultItemRequest.php`
- `name` → required|string|max:191
- `payload` → nullable|array
- `notes` → nullable|string|max:5000

### `Docs/UploadDocumentRequest.php`
- `file` → required|file|max:25600|mimes:pdf,docx
- `category` → required|in:identity,license,insurance,agreement,other
- `expires_at` → nullable|date|after:today

### `Docs/SendForSignatureRequest.php`
- `signers` → required|array|min:1
- `signers.*.user_id` → required|uuid|exists:users,id
- `signers.*.role` → required|in:practitioner,cs,ss

### `Docs/AmendDocumentRequest.php`
- `changes` → required|array

### `Docs/RequestReleaseRequest.php`
- `steward_id` → required|uuid|exists:users,id

### `Docs/SignReminderRequest.php`
- `signer_id` → required|uuid|exists:users,id

### `Network/RequestConnectionRequest.php`
- `recipient_id` → required|uuid|exists:users,id
- `message` → nullable|string|max:500

### `Network/AddShadowRequest.php`
- `shadow_user_id` → required|uuid|exists:users,id

### `Network/SendReferralRequest.php`
- `recipient_id` → required|uuid|exists:users,id
- `client_name` → required|string|max:191
- `notes` → nullable|string|max:5000

### `Referrals/CreateReferralRequest.php`
- alias of `Network/SendReferralRequest`

### `Roster/UpsertRosterEntryRequest.php`
- `client_name` → required|string|max:191
- `client_priority` → required|integer|between:1,5
- `notes` → nullable|string

### `Services/CreateServiceRequest.php`
- `name` → required|string|max:191
- `description` → required|string|max:2000
- `fee_cents` → nullable|integer|min:0
- `is_public` → required|boolean

### `Services/UpdateServiceRequest.php`
- inherits CreateServiceRequest

### `Profile/UpdateAvailabilityRequest.php`
- `availability_status` → required|in:accepting,waitlist,closed

### `Profile/UpdateSpecialtiesRequest.php`
- `specialties` → required|array|min:1
- `specialties.*` → string|max:100

### `Profile/UpdateVisibilityRequest.php`
- `*_public` → boolean (where * ∈ practitioner|cs|business_partner|ss_contact)

### `Subscription/ChangeTierRequest.php`
- `target_tier` → required|in:access,practice

### `Subscription/ToggleAddOnRequest.php`
- `addon` → required|in:maat,services_mode
- `enabled` → required|boolean

### `Subscription/CancelSubscriptionRequest.php`
- `reason` → required|in:cost,not_using,switching_provider,other
- `feedback` → nullable|string|max:2000

### `Settings/UpdateNotificationGatesRequest.php`
- `gates` → required|array
- `gates.*` → boolean

### `Settings/UpdatePreferencesRequest.php`
- `theme` → nullable|in:gold,dark,system
- `timezone` → nullable|timezone

### `Settings/CloseAccountRequest.php`
- `reason` → required|string|max:2000
- `confirmation` → required|in:CLOSE

### `Settings/ExportDataRequest.php`
- `scope` → required|in:all,profile_only,messages_only

### `Messages/CreateThreadRequest.php`
- `participants` → required|array|min:1
- `participants.*` → uuid|exists:users,id
- `subject` → required|string|max:191
- `initial_body` → required|string

### `Messages/SendMessageRequest.php`
- `thread_id` → required|uuid|exists:message_threads,id
- `body` → required|string|max:50000

### `Team/InviteTeamMemberRequest.php`
- `email` → required|email
- `permissions` → required|array

### `Team/UpdatePermissionsRequest.php`
- `permissions` → required|array

### `Business/AddLineItemRequest.php`
- `description` → required|string|max:500
- `quantity` → required|numeric|min:0.01
- `unit_cents` → required|integer|min:0

### `Business/UploadTaxDocRequest.php`
- `file` → required|file|max:25600|mimes:pdf,jpg,png

### `Business/W9SubmissionRequest.php`
- `file` → required|file|max:10240|mimes:pdf
- `legal_name` → required|string|max:191
- `tin` → required|string|max:20

### `Business/UpdateProposalRequest.php`
- `cover_letter` → required|string|max:5000
- `bid_cents` → required|integer|min:0
- `start_date` → nullable|date

### `Business/SubmitMilestoneRequest.php`
- `milestone_id` → required|uuid
- `summary` → required|string|max:5000
- `attachments` → nullable|array

### `Support/CreateTicketRequest.php`
- `subject` → required|string|max:191
- `body` → required|string|max:5000
- `category` → required|in:bug,feature_request,billing,abuse,other
- `priority` → required|in:low,normal,high

### `Support/ReplyTicketRequest.php`
- `body` → required|string|max:5000

### `Support/SubmitFeedbackRequest.php`
- `body` → required|string|max:5000
- `channel` → required|in:feedback_button,contextual_questionnaire
- `nps_score` → nullable|integer|between:0,10

### `Admin/HelpArticleRequest.php`
- `title` → required|string|max:191
- `body` → required|string
- `category` → required|string|max:50
- `sort_order` → nullable|integer

### `Admin/ManualPayoutRequest.php`
- `reason` → required|string|max:1000

### `Admin/ImpersonateUserRequest.php`
- `reason` → required|string|max:500

### `Admin/DeactivateUserRequest.php`
- `reason` → required|string|max:1000

### `Admin/CreateRoleRequest.php`
- `name` → required|string|max:100|unique:roles,name
- `description` → nullable|string|max:500

### `Admin/UpdateRolePermissionsRequest.php`
- `permissions` → required|array



### `News/CreateNewsPostRequest.php`
- `body` → required|string|max:10000
- `category` → required|in:announcement,question,resource,event,poll
- `poll_options` → required_if:category,poll|array|min:2|max:6
- `pinned` → boolean

### `News/UpdateNewsPostRequest.php`
- inherits CreateNewsPostRequest; all fields optional

### `News/CreateCommentRequest.php`
- `body` → required|string|max:2000

### `News/ReactionRequest.php`
- `reaction` → required|in:heart,clap,insight,question

### `News/PollVoteRequest.php`
- `option_index` → required|integer|between:0,5

### `News/RsvpEventRequest.php`
- `response` → required|in:yes,no,maybe


# Section 10 — Routes

`routes/web.php` — Inertia routes. `routes/api.php` reserved for Sanctum-token API consumers (not used by the Vue SPA, which goes through Inertia/web). All routes named.

```php
<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{Auth, Provider, ContinuitySteward, SupportSteward, BusinessPartner, Admin, Shared, Public as PublicCtl};

// ───────────────────────────────────────────────────────────────────────────
// Public routes (no auth)
// ───────────────────────────────────────────────────────────────────────────
Route::prefix('public')->name('public.')->group(function () {
    Route::get('/provider',            [PublicCtl\ProfileController::class, 'showProvider'])->name('provider');
    Route::get('/continuity-steward',  [PublicCtl\ProfileController::class, 'showCs'])->name('cs');
    Route::get('/support-steward',     [PublicCtl\ProfileController::class, 'showSs'])->name('ss');
    Route::get('/business',            [PublicCtl\ProfileController::class, 'showBusiness'])->name('business');
});

// ───────────────────────────────────────────────────────────────────────────
// Auth (guest)
// ───────────────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get ('/login',             [Auth\LoginController::class, 'show'])->name('login');
    Route::post('/login',             [Auth\LoginController::class, 'store'])->name('login.store');
    Route::get ('/register',          [Auth\RegisterController::class, 'show'])->name('register');
    Route::post('/register',          [Auth\RegisterController::class, 'store'])->name('register.store');
    Route::get ('/password/forgot',   [Auth\PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/password/forgot',   [Auth\PasswordResetController::class, 'sendLink'])->name('password.email');
    Route::get ('/password/reset/{token}', [Auth\PasswordResetController::class, 'show'])->name('password.reset');
    Route::post('/password/reset',    [Auth\PasswordResetController::class, 'store'])->name('password.update');
});

// ───────────────────────────────────────────────────────────────────────────
// Authenticated baseline
// ───────────────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'check.locked'])->group(function () {
    Route::post('/logout', [Auth\LoginController::class, 'destroy'])->name('logout');

    // MFA flow
    Route::get ('/mfa/setup',     [Auth\MfaController::class, 'setup'])->name('mfa.setup');
    Route::post('/mfa/confirm',   [Auth\MfaController::class, 'confirm'])->name('mfa.confirm');
    Route::post('/mfa/disable',   [Auth\MfaController::class, 'disable'])->name('mfa.disable');
    Route::get ('/mfa/challenge', [Auth\MfaController::class, 'challenge'])->name('mfa.challenge');
    Route::post('/mfa/verify',    [Auth\MfaController::class, 'verify'])->name('mfa.verify');

    // ─── Shared pages (overview, messages, activity, support) ─────────────
    foreach (['provider', 'cs', 'ss', 'bp'] as $portal) {
        Route::prefix($portal)->name("$portal.")->group(function () use ($portal) {
            Route::get ('/overview',                    [Shared\OverviewController::class, 'index'])->name('overview');
            Route::get ('/messages',                    [Shared\MessagesController::class, 'index'])->name('messages');
            Route::get ('/messages/{thread}',           [Shared\MessagesController::class, 'show'])->name('messages.show');
            Route::post('/messages',                    [Shared\MessagesController::class, 'createThread'])->name('messages.create');
            Route::post('/messages/{thread}',           [Shared\MessagesController::class, 'store'])->name('messages.store');
            Route::post('/messages/{thread}/read',      [Shared\MessagesController::class, 'markRead'])->name('messages.read');
            Route::post('/messages/{thread}/archive',   [Shared\MessagesController::class, 'archive'])->name('messages.archive');
            Route::get ('/activity',                    [Shared\ActivityController::class, 'index'])->name('activity');
            Route::post('/activity/{event}/read',       [Shared\ActivityController::class, 'markRead'])->name('activity.read');
            Route::post('/activity/mark-all-read',      [Shared\ActivityController::class, 'markAllRead'])->name('activity.read-all');
            Route::get ('/support',                     [Shared\SupportController::class, 'index'])->name('support');
            Route::post('/support/tickets',             [Shared\SupportController::class, 'createTicket'])->name('support.tickets.store');
            Route::post('/support/feedback',            [Shared\SupportController::class, 'submitFeedback'])->name('support.feedback');
            Route::post('/support/tickets/{complaint}/reply', [Shared\SupportController::class, 'reply'])->name('support.reply');
        });
    }

    // ─── Provider portal ──────────────────────────────────────────────────
    Route::prefix('provider')->middleware('role:practitioner')->name('provider.')->group(function () {
        Route::get ('/dashboard',                   [Provider\DashboardController::class, 'index'])->name('dashboard');

        // Profile
        Route::get ('/edit-profile',                [Provider\ProfileController::class, 'edit'])->name('profile.edit');
        Route::post('/profile/basic',               [Provider\ProfileController::class, 'updateBasic'])->name('profile.basic');
        Route::post('/profile/credentials',         [Provider\ProfileController::class, 'updateCredentials'])->name('profile.credentials');
        Route::post('/profile/fees',                [Provider\ProfileController::class, 'updateFees'])->name('profile.fees');
        Route::post('/profile/availability',        [Provider\ProfileController::class, 'updateAvailability'])->name('profile.availability');
        Route::post('/profile/visibility',          [Provider\ProfileController::class, 'updateVisibility'])->name('profile.visibility');
        Route::post('/profile/slug',                [Provider\ProfileController::class, 'lockSlug'])->name('profile.slug');

        // Continuity plan
        Route::get ('/continuity-plan',                                  [Provider\ContinuityPlanController::class, 'index'])->name('plan');
        Route::post('/continuity-plan',                                  [Provider\ContinuityPlanController::class, 'create'])->name('plan.create');
        Route::patch('/continuity-plan/{plan}',                          [Provider\ContinuityPlanController::class, 'update'])->name('plan.update');
        Route::post('/continuity-plan/{plan}/incidents',                 [Provider\ContinuityPlanController::class, 'configureIncidents'])->name('plan.incidents');
        Route::post('/continuity-plan/{plan}/send',                      [Provider\ContinuityPlanController::class, 'send'])->name('plan.send');
        Route::post('/continuity-plan/{plan}/sign',                      [Provider\ContinuityPlanController::class, 'sign'])->name('plan.sign');
        Route::post('/continuity-plan/{plan}/review/begin',              [Provider\ContinuityPlanController::class, 'beginReview'])->name('plan.review.begin');
        Route::post('/continuity-plan/{plan}/review/complete',           [Provider\ContinuityPlanController::class, 'completeReview'])->name('plan.review.complete');
        Route::post('/continuity-plan/{plan}/attest-vault',              [Provider\ContinuityPlanController::class, 'attestVault'])->name('plan.attest-vault');
        Route::post('/continuity-plan/{plan}/tasks',                     [Provider\ContinuityPlanController::class, 'addTask'])->name('plan.tasks.store');

        // Stewards
        Route::get ('/continuity-stewards',                              [Provider\ContinuityStewardController::class, 'index'])->name('cs');
        Route::post('/continuity-stewards/invite-existing',              [Provider\ContinuityStewardController::class, 'inviteExisting'])->name('cs.invite-existing');
        Route::post('/continuity-stewards/invite-external',              [Provider\ContinuityStewardController::class, 'inviteExternal'])->name('cs.invite-external');
        Route::delete('/continuity-stewards/{designation}',              [Provider\ContinuityStewardController::class, 'remove'])->name('cs.remove');
        Route::post('/continuity-stewards/{designation}/activate-alternate', [Provider\ContinuityStewardController::class, 'activateAlternate'])->name('cs.activate-alternate');

        Route::get ('/support-stewards',                                 [Provider\SupportStewardController::class, 'index'])->name('ss');
        Route::post('/support-stewards/invite',                          [Provider\SupportStewardController::class, 'inviteExternal'])->name('ss.invite');
        Route::delete('/support-stewards/{designation}',                 [Provider\SupportStewardController::class, 'remove'])->name('ss.remove');

        // Network
        Route::get ('/network',                              [Provider\NetworkController::class, 'index'])->name('network');
        Route::post('/network/request',                      [Provider\NetworkController::class, 'request'])->name('network.request');
        Route::post('/network/requests/{req}/accept',        [Provider\NetworkController::class, 'accept'])->name('network.accept');
        Route::post('/network/requests/{req}/decline',       [Provider\NetworkController::class, 'decline'])->name('network.decline');
        Route::post('/network/{conn}/archive',               [Provider\NetworkController::class, 'archive'])->name('network.archive');
        Route::post('/network/shadow',                       [Provider\NetworkController::class, 'addShadow'])->name('network.shadow');
        Route::delete('/network/shadow/{shadowConnection}',  [Provider\NetworkController::class, 'removeShadow'])->name('network.shadow.remove');
        Route::post('/network/shadow/{shadow}/invite',       [Provider\NetworkController::class, 'inviteShadow'])->name('network.shadow.invite');
        Route::put ('/network/config',                       [Provider\NetworkController::class, 'saveNetworkConfig'])->name('network.config.save');
        Route::post('/network/config/reset',                 [Provider\NetworkController::class, 'resetNetworkConfig'])->name('network.config.reset');

        // Services (gated by services_mode)
        Route::middleware('services.mode')->group(function () {
            Route::get   ('/services',                          [Provider\ServicesController::class, 'index'])->name('services');
            Route::post  ('/services',                          [Provider\ServicesController::class, 'store'])->name('services.store');
            Route::patch ('/services/{service}',                [Provider\ServicesController::class, 'update'])->name('services.update');
            Route::post  ('/services/{service}/toggle',         [Provider\ServicesController::class, 'toggleStatus'])->name('services.toggle');
            Route::delete('/services/{service}',                [Provider\ServicesController::class, 'destroy'])->name('services.destroy');
            Route::post  ('/services/requests/{req}/accept',    [Provider\ServicesController::class, 'acceptRequest'])->name('services.requests.accept');
            Route::post  ('/services/requests/{req}/decline',   [Provider\ServicesController::class, 'declineRequest'])->name('services.requests.decline');
        });

        // Job postings
        Route::get   ('/job-postings',                                [Provider\JobPostingsController::class, 'index'])->name('jobs');
        Route::post  ('/job-postings',                                [Provider\JobPostingsController::class, 'store'])->name('jobs.store');
        Route::post  ('/job-postings/{job}/publish',                  [Provider\JobPostingsController::class, 'publish'])->name('jobs.publish');
        Route::patch ('/job-postings/{job}',                          [Provider\JobPostingsController::class, 'update'])->name('jobs.update');
        Route::post  ('/job-postings/{job}/pause',                    [Provider\JobPostingsController::class, 'pause'])->name('jobs.pause');
        Route::post  ('/job-postings/{job}/close',                    [Provider\JobPostingsController::class, 'close'])->name('jobs.close');
        Route::post  ('/job-postings/proposals/{proposal}/review',    [Provider\JobPostingsController::class, 'reviewProposal'])->name('jobs.proposals.review');
        Route::post  ('/job-postings/proposals/{proposal}/accept',    [Provider\JobPostingsController::class, 'acceptProposal'])->name('jobs.proposals.accept');
        Route::post  ('/job-postings/proposals/{proposal}/decline',   [Provider\JobPostingsController::class, 'declineProposal'])->name('jobs.proposals.decline');

        // Referrals
        Route::get ('/referrals',                       [Provider\ReferralsController::class, 'index'])->name('referrals');
        Route::post('/referrals',                       [Provider\ReferralsController::class, 'store'])->name('referrals.store');
        Route::post('/referrals/{ref}/accept',          [Provider\ReferralsController::class, 'accept'])->name('referrals.accept');
        Route::post('/referrals/{ref}/decline',         [Provider\ReferralsController::class, 'decline'])->name('referrals.decline');
        Route::post('/referrals/{ref}/close',           [Provider\ReferralsController::class, 'close'])->name('referrals.close');
        Route::post('/referrals/{ref}/cancel',          [Provider\ReferralsController::class, 'cancel'])->name('referrals.cancel');

        // Vault (requires active plan)
        Route::middleware('plan.active')->group(function () {
            Route::get   ('/vault',                       [Provider\VaultController::class, 'index'])->name('vault');
            Route::post  ('/vault/upload',                [Provider\VaultController::class, 'upload'])->name('vault.upload');
            Route::patch ('/vault/{item}',                [Provider\VaultController::class, 'update'])->name('vault.update');
            Route::delete('/vault/{item}',                [Provider\VaultController::class, 'destroy'])->name('vault.destroy');
            Route::get   ('/vault/{item}/download',       [Provider\VaultController::class, 'download'])->name('vault.download');
            Route::post  ('/vault/attest',                [Provider\VaultController::class, 'attest'])->name('vault.attest');
            Route::post  ('/vault/permissions',           [Provider\VaultController::class, 'setPermissions'])->name('vault.permissions');
        });

        // Important Documents
        Route::get ('/important-documents',                       [Provider\DocumentsController::class, 'index'])->name('docs');
        Route::get ('/important-documents/{doc}/download',        [Provider\DocumentsController::class, 'download'])->name('docs.download');
        Route::post('/important-documents/{doc}/archive',         [Provider\DocumentsController::class, 'archive'])->name('docs.archive');
        Route::post('/important-documents/upload',                [Provider\DocumentsController::class, 'upload'])->name('docs.upload');

        // Finances
        Route::get ('/finances',                                  [Provider\FinancesController::class, 'index'])->name('finances');
        Route::post('/finances/invoices/{inv}/approve',           [Provider\FinancesController::class, 'approveInvoice'])->name('finances.invoices.approve');
        Route::post('/finances/invoices/{inv}/dispute',           [Provider\FinancesController::class, 'disputeInvoice'])->name('finances.invoices.dispute');

        // Settings
        Route::get ('/settings',                          [Provider\SettingsController::class, 'index'])->name('settings');
        Route::post('/settings/notifications',            [Provider\SettingsController::class, 'updateNotificationGates'])->name('settings.notifications');
        Route::post('/settings/preferences',              [Provider\SettingsController::class, 'updatePreferences'])->name('settings.preferences');
        Route::post('/settings/upgrade',                  [Provider\SettingsController::class, 'upgradeTier'])->name('settings.upgrade');
        Route::post('/settings/downgrade',                [Provider\SettingsController::class, 'downgradeTier'])->name('settings.downgrade');
        Route::post('/settings/services-mode',            [Provider\SettingsController::class, 'toggleServicesMode'])->name('settings.services-mode');
        Route::post('/settings/maat',                     [Provider\SettingsController::class, 'toggleMaatAddon'])->name('settings.maat');
    });

    // ─── CS portal ────────────────────────────────────────────────────────
    Route::prefix('cs')->middleware('role:continuity_steward')->name('cs.')->group(function () {
        Route::get ('/dashboard',                                       [ContinuitySteward\DashboardController::class, 'index'])->name('dashboard');
        Route::get ('/edit-profile',                                    [ContinuitySteward\ProfileController::class, 'edit'])->name('profile.edit');
        Route::post('/profile',                                         [ContinuitySteward\ProfileController::class, 'update'])->name('profile.update');
        Route::get ('/providers',                                       [ContinuitySteward\ProvidersController::class, 'index'])->name('providers');
        Route::get ('/my-tasks',                                        [ContinuitySteward\TasksController::class, 'index'])->name('tasks');
        Route::post('/my-tasks/{task}/complete',                        [ContinuitySteward\TasksController::class, 'complete'])->name('tasks.complete');
        Route::post('/my-tasks/{task}/exception',                       [ContinuitySteward\TasksController::class, 'flagException'])->name('tasks.exception');
        Route::get ('/continuity-management',                           [ContinuitySteward\ContinuityManagementController::class, 'index'])->name('management');
        Route::post('/continuity-management/{designation}/certify',     [ContinuitySteward\ContinuityManagementController::class, 'certify'])->name('management.certify');
        Route::post('/continuity-management/{designation}/checkin',     [ContinuitySteward\ContinuityManagementController::class, 'addCheckin'])->name('management.checkin');
        Route::post('/continuity-management/{designation}/report-incident', [ContinuitySteward\ContinuityManagementController::class, 'reportIncident'])->name('management.incident');
        Route::post('/incidents/{incident}/verify',                     [ContinuitySteward\ContinuityManagementController::class, 'verifyIncident'])->name('incidents.verify');
        Route::post('/incidents/{incident}/activate',                   [ContinuitySteward\ContinuityManagementController::class, 'activateIncident'])->name('incidents.activate');
        Route::post('/incidents/{incident}/close',                      [ContinuitySteward\ContinuityManagementController::class, 'closeIncident'])->name('incidents.close');
        Route::get ('/important-documents',                             [ContinuitySteward\DocumentsController::class, 'index'])->name('docs');
        Route::post('/important-documents/{doc}/sign',                  [ContinuitySteward\DocumentsController::class, 'sign'])->name('docs.sign');
        Route::get ('/important-documents/{doc}/download',              [ContinuitySteward\DocumentsController::class, 'download'])->name('docs.download');

        // Vault (requires active incident on at least one assigned plan)
        Route::middleware('incident.active')->group(function () {
            Route::get ('/vault',                              [ContinuitySteward\VaultController::class, 'index'])->name('vault');
            Route::post('/vault/{item}/reveal',                [ContinuitySteward\VaultController::class, 'reveal'])->name('vault.reveal');
            Route::get ('/vault/{item}/download',              [ContinuitySteward\VaultController::class, 'download'])->name('vault.download');
        });

        Route::get ('/finances',                          [ContinuitySteward\FinancesController::class, 'index'])->name('finances');
        Route::post('/finances/stripe/onboard',           [ContinuitySteward\FinancesController::class, 'stripeOnboard'])->name('finances.stripe.onboard');
        Route::get ('/settings',                          [ContinuitySteward\SettingsController::class, 'index'])->name('settings');
        Route::post('/settings/upgrade',                  [ContinuitySteward\SettingsController::class, 'upgradeFromInvited'])->name('settings.upgrade');
    });

    // ─── SS portal ────────────────────────────────────────────────────────
    Route::prefix('ss')->middleware('role:support_steward')->name('ss.')->group(function () {
        Route::get ('/dashboard',                                  [SupportSteward\DashboardController::class, 'index'])->name('dashboard');
        Route::get ('/edit-profile',                               [SupportSteward\ProfileController::class, 'edit'])->name('profile.edit');
        Route::post('/profile',                                    [SupportSteward\ProfileController::class, 'update'])->name('profile.update');
        Route::get ('/providers',                                  [SupportSteward\ProvidersController::class, 'index'])->name('providers');
        Route::post('/providers/{plan}/notify-unresponsive',       [SupportSteward\ProvidersController::class, 'notifyUnresponsive'])->name('providers.notify');
        Route::get ('/continuity-stewards',                        [SupportSteward\ContinuityStewardsController::class, 'index'])->name('cs');
        Route::get ('/my-tasks',                                   [SupportSteward\TasksController::class, 'index'])->name('tasks');
        Route::post('/my-tasks/{task}/complete',                   [SupportSteward\TasksController::class, 'complete'])->name('tasks.complete');
        Route::get ('/critical-incident-log',                      [SupportSteward\CriticalIncidentController::class, 'index'])->name('incidents');
        Route::post('/critical-incident-log/report',               [SupportSteward\CriticalIncidentController::class, 'report'])->name('incidents.report');
        Route::post('/critical-incident-log/{incident}/escalate',  [SupportSteward\CriticalIncidentController::class, 'escalate'])->name('incidents.escalate');
        Route::get ('/important-documents',                        [SupportSteward\DocumentsController::class, 'index'])->name('docs');
        Route::get ('/important-documents/{doc}/download',         [SupportSteward\DocumentsController::class, 'download'])->name('docs.download');
    });

    // ─── BP portal ────────────────────────────────────────────────────────
    Route::prefix('bp')->middleware('role:business_partner')->name('bp.')->group(function () {
        Route::get ('/dashboard',                          [BusinessPartner\DashboardController::class, 'index'])->name('dashboard');
        Route::get ('/edit-profile',                       [BusinessPartner\ProfileController::class, 'edit'])->name('profile.edit');
        Route::post('/profile',                            [BusinessPartner\ProfileController::class, 'update'])->name('profile.update');

        Route::get ('/find-jobs',                          [BusinessPartner\JobsController::class, 'index'])->name('jobs');
        Route::get ('/find-jobs/{job}',                    [BusinessPartner\JobsController::class, 'show'])->name('jobs.show');
        Route::post('/find-jobs/{job}/save',               [BusinessPartner\JobsController::class, 'save'])->name('jobs.save');
        Route::post('/find-jobs/{job}/unsave',             [BusinessPartner\JobsController::class, 'unsave'])->name('jobs.unsave');

        Route::get ('/proposals',                          [BusinessPartner\ProposalsController::class, 'index'])->name('proposals');
        Route::post('/proposals',                          [BusinessPartner\ProposalsController::class, 'store'])->name('proposals.store');
        Route::patch('/proposals/{proposal}',              [BusinessPartner\ProposalsController::class, 'update'])->name('proposals.update');
        Route::post('/proposals/{proposal}/withdraw',      [BusinessPartner\ProposalsController::class, 'withdraw'])->name('proposals.withdraw');

        Route::get ('/contracts',                          [BusinessPartner\ContractsController::class, 'index'])->name('contracts');
        Route::get ('/contracts/{contract}',               [BusinessPartner\ContractsController::class, 'show'])->name('contracts.show');
        Route::post('/contracts/{contract}/sign',          [BusinessPartner\ContractsController::class, 'signByBp'])->name('contracts.sign');
        Route::post('/contracts/{contract}/cancel',        [BusinessPartner\ContractsController::class, 'cancel'])->name('contracts.cancel');
        Route::post('/contracts/{contract}/reassign',      [BusinessPartner\ContractsController::class, 'reassign'])->name('contracts.reassign');

        Route::get ('/milestones',                         [BusinessPartner\MilestonesController::class, 'index'])->name('milestones');
        Route::post('/milestones/{milestone}/submit',      [BusinessPartner\MilestonesController::class, 'submit'])->name('milestones.submit');

        Route::get ('/invoices',                           [BusinessPartner\InvoicesController::class, 'index'])->name('invoices');
        Route::post('/invoices',                           [BusinessPartner\InvoicesController::class, 'store'])->name('invoices.store');
        Route::post('/invoices/{inv}/line-items',          [BusinessPartner\InvoicesController::class, 'addLineItem'])->name('invoices.line-items');
        Route::post('/invoices/{inv}/send',                [BusinessPartner\InvoicesController::class, 'send'])->name('invoices.send');
        Route::post('/invoices/{inv}/void',                [BusinessPartner\InvoicesController::class, 'void'])->name('invoices.void');

        Route::get ('/finances',                           [BusinessPartner\FinancesController::class, 'index'])->name('finances');
        Route::get ('/payment-setup',                      [BusinessPartner\PaymentSetupController::class, 'index'])->name('payment-setup');
        Route::post('/payment-setup/onboard',              [BusinessPartner\PaymentSetupController::class, 'onboard'])->name('payment-setup.onboard');
        Route::post('/payment-setup/refresh',              [BusinessPartner\PaymentSetupController::class, 'refreshState'])->name('payment-setup.refresh');
        Route::post('/payment-setup/tax-docs',             [BusinessPartner\PaymentSetupController::class, 'uploadTaxDoc'])->name('payment-setup.tax-docs');

        // Team (agency only — enforced inside controller via bp_type check)
        Route::get   ('/team',                             [BusinessPartner\TeamController::class, 'index'])->name('team');
        Route::post  ('/team/invite',                      [BusinessPartner\TeamController::class, 'invite'])->name('team.invite');
        Route::patch ('/team/members/{member}',            [BusinessPartner\TeamController::class, 'updatePermissions'])->name('team.permissions');
        Route::delete('/team/members/{member}',            [BusinessPartner\TeamController::class, 'remove'])->name('team.remove');
    });

    // ─── Admin portal ─────────────────────────────────────────────────────
    Route::prefix('admin')->middleware('admin')->name('admin.')->group(function () {
        Route::get ('/dashboard',                          [Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::get ('/packages',                           [Admin\PackagesController::class, 'index'])->name('packages');
        Route::post('/packages',                           [Admin\PackagesController::class, 'store'])->name('packages.store');
        Route::patch('/packages/{pkg}',                    [Admin\PackagesController::class, 'update'])->name('packages.update');
        Route::post('/packages/{pkg}/expire',              [Admin\PackagesController::class, 'expire'])->name('packages.expire');

        Route::get ('/users',                              [Admin\UsersController::class, 'index'])->name('users');
        Route::get ('/users/{user}',                       [Admin\UsersController::class, 'show'])->name('users.show');
        Route::post('/users/{user}/lock',                  [Admin\UsersController::class, 'lock'])->name('users.lock');
        Route::post('/users/{user}/unlock',                [Admin\UsersController::class, 'unlock'])->name('users.unlock');
        Route::post('/users/{user}/force-reset',           [Admin\UsersController::class, 'forceReset'])->name('users.force-reset');
        Route::post('/users/{user}/role',                  [Admin\UsersController::class, 'changeRole'])->name('users.role');
        Route::post('/users/{user}/deactivate',            [Admin\UsersController::class, 'deactivate'])->name('users.deactivate');
        Route::post('/users/{user}/reactivate',            [Admin\UsersController::class, 'reactivate'])->name('users.reactivate');
        Route::post('/users/{user}/impersonate',           [Admin\UsersController::class, 'impersonate'])->name('users.impersonate');

        Route::get   ('/roles',                            [Admin\RolesController::class, 'index'])->name('roles');
        Route::post  ('/roles',                            [Admin\RolesController::class, 'store'])->name('roles.store');
        Route::patch ('/roles/{role}',                     [Admin\RolesController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}',                     [Admin\RolesController::class, 'destroy'])->name('roles.destroy');
        Route::post  ('/roles/{role}/assign',              [Admin\RolesController::class, 'assign'])->name('roles.assign');

        Route::get ('/payments',                           [Admin\PaymentsController::class, 'index'])->name('payments');
        Route::get ('/payments/webhooks',                  [Admin\PaymentsController::class, 'webhookEvents'])->name('payments.webhooks');
        Route::get ('/payments/{payment}',                 [Admin\PaymentsController::class, 'show'])->name('payments.show');
        Route::post('/payments/{payment}/refund',          [Admin\PaymentsController::class, 'refund'])->name('payments.refund');

        Route::get ('/complaints',                                [Admin\ComplaintsController::class, 'index'])->name('complaints');
        Route::get ('/complaints/{complaint}',                    [Admin\ComplaintsController::class, 'show'])->name('complaints.show');
        Route::post('/complaints/{complaint}/assign',             [Admin\ComplaintsController::class, 'assign'])->name('complaints.assign');
        Route::post('/complaints/{complaint}/status',             [Admin\ComplaintsController::class, 'changeStatus'])->name('complaints.status');
        Route::post('/complaints/{complaint}/priority',           [Admin\ComplaintsController::class, 'changePriority'])->name('complaints.priority');
        Route::post('/complaints/{complaint}/reply',              [Admin\ComplaintsController::class, 'reply'])->name('complaints.reply');
        Route::post('/complaints/{complaint}/resolve',            [Admin\ComplaintsController::class, 'resolve'])->name('complaints.resolve');
        Route::post('/complaints/{complaint}/close',              [Admin\ComplaintsController::class, 'close'])->name('complaints.close');
    });
});

// ───────────────────────────────────────────────────────────────────────────
// Stripe webhook (no auth — Stripe signs it)
// ───────────────────────────────────────────────────────────────────────────
Route::post('/stripe/webhook', [\Laravel\Cashier\Http\Controllers\WebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook');
```

---


---

## Additional Routes (added in corrected version)

Concise route definitions for the new controller methods listed in the Additional Controllers block above. All routes use named-route convention `{portal}.{resource}.{action}` and inherit the portal middleware group (`auth + EnsureRole`).

### Provider

```php
Route::middleware(['auth','role:practitioner'])->prefix('provider')->name('provider.')->group(function () {
    // Roster
    Route::get('/roster',                      [RosterController::class,'index'])->name('roster.index');
    Route::post('/roster',                     [RosterController::class,'store'])->name('roster.store');
    Route::patch('/roster/{item}',             [RosterController::class,'update'])->name('roster.update');
    Route::post('/roster/{item}/discharge',    [RosterController::class,'discharge'])->name('roster.discharge');
    // Documents (new methods)
    Route::post('/documents/library',                  [DocumentsController::class,'uploadLibrary'])->name('documents.library');
    Route::post('/documents/request',                  [DocumentsController::class,'requestFromSteward'])->name('documents.request');
    Route::post('/documents/{doc}/send-signature',     [DocumentsController::class,'sendForSignature'])->name('documents.send-signature');
    Route::post('/documents/{doc}/sign',               [DocumentsController::class,'sign'])->name('documents.sign');
    Route::post('/documents/{doc}/amend',              [DocumentsController::class,'amend'])->name('documents.amend');
    Route::post('/documents/{doc}/renew',              [DocumentsController::class,'renew'])->name('documents.renew');
    Route::post('/documents/{doc}/archive',            [DocumentsController::class,'archive'])->name('documents.archive');
    Route::delete('/documents/{doc}',                  [DocumentsController::class,'deleteDraft'])->name('documents.delete');
    Route::post('/documents/{doc}/release-request',    [DocumentsController::class,'requestRelease'])->name('documents.release-request');
    Route::post('/documents/{doc}/reminder',           [DocumentsController::class,'sendReminder'])->name('documents.reminder');
    // Vault (new)
    Route::patch('/vault/items/{item}',                [VaultController::class,'update'])->name('vault.update');
    Route::delete('/vault/items/{item}',               [VaultController::class,'delete'])->name('vault.delete');
    // Network (new)
    Route::get('/network/business-partners',           [NetworkController::class,'browseBPs'])->name('network.bps');
    Route::post('/network/{user}/refer',               [NetworkController::class,'sendReferralFromNetwork'])->name('network.refer');
    Route::patch('/network/specialties',               [NetworkController::class,'quickEditSpecialties'])->name('network.specialties');
    // Referrals (new)
    Route::post('/referrals/{ref}/cancel',             [ReferralsController::class,'cancel'])->name('referrals.cancel');
    Route::post('/referrals/{ref}/respond',            [ReferralsController::class,'respond'])->name('referrals.respond');
    // Services (new)
    Route::post('/services/{svc}/toggle-public',       [ServicesController::class,'togglePublic'])->name('services.toggle-public');
    // Job postings (new)
    Route::post('/job-postings',                       [JobPostingsController::class,'store'])->name('job-postings.store');
    Route::post('/job-postings/{job}/close',           [JobPostingsController::class,'close'])->name('job-postings.close');
    // Stewards (new)
    Route::post('/support-stewards/primary',           [ContinuityStewardController::class,'designatePrimarySs'])->name('support-stewards.primary');
    Route::post('/support-stewards/alternate',         [ContinuityStewardController::class,'designateAlternateSs'])->name('support-stewards.alternate');
    Route::post('/support-stewards/{designation}/copy-tasks', [ContinuityStewardController::class,'copyTasks'])->name('support-stewards.copy-tasks');
    Route::delete('/support-stewards/{designation}',   [ContinuityStewardController::class,'removeSs'])->name('support-stewards.remove');
    Route::post('/continuity-stewards/{designation}/resend',         [ContinuityStewardController::class,'resendInvite'])->name('continuity-stewards.resend');
    Route::patch('/continuity-stewards/{designation}/permissions',   [ContinuityStewardController::class,'setPermissions'])->name('continuity-stewards.permissions');
    Route::patch('/continuity-stewards/{designation}/payment-model', [ContinuityStewardController::class,'setPaymentModel'])->name('continuity-stewards.payment-model');
    // Finances (new)
    Route::post('/finances/invoices/{inv}/pay',                                   [FinancesController::class,'payInvoice'])->name('finances.pay-invoice');
    Route::post('/finances/contracts/{ctr}/sign',                                 [FinancesController::class,'signContract'])->name('finances.sign-contract');
    Route::post('/finances/contracts/{ctr}/cancel',                               [FinancesController::class,'cancelContract'])->name('finances.cancel-contract');
    Route::post('/finances/contracts/{ctr}/milestones/{ms}/approve',              [FinancesController::class,'approveMilestone'])->name('finances.approve-milestone');
    Route::post('/finances/invoices/{inv}/status',                                [FinancesController::class,'overrideInvoiceStatus'])->name('finances.invoice-status');
    // Settings (new)
    Route::post('/settings/billing/payment-methods',   [SettingsController::class,'addPaymentMethod'])->name('settings.payment-methods');
    Route::post('/settings/billing/autopay',           [SettingsController::class,'setAutopay'])->name('settings.autopay');
    Route::post('/settings/billing/cancel',            [SettingsController::class,'cancelSubscription'])->name('settings.cancel');
    Route::post('/settings/security/password',         [SettingsController::class,'changePassword'])->name('settings.password');
    Route::post('/settings/security/mfa',              [SettingsController::class,'toggleMfa'])->name('settings.mfa');
    Route::post('/settings/data/export',               [SettingsController::class,'exportData'])->name('settings.export');
    Route::delete('/settings/security/sessions/{sess}',[SettingsController::class,'revokeSession'])->name('settings.revoke-session');
    Route::post('/settings/profile/services',          [SettingsController::class,'updateServicesOffered'])->name('settings.services-offered');
    Route::post('/settings/intent',                    [SettingsController::class,'setIntentSegment'])->name('settings.intent');
    Route::post('/profile/education',                  [ProfileController::class,'addEducation'])->name('profile.education');
});
```

### Continuity Steward

```php
Route::middleware(['auth','role:continuity_steward'])->prefix('cs')->name('cs.')->group(function () {
    // Invoices (new controller)
    Route::resource('/invoices', InvoicesController::class)->only(['index','store']);
    Route::post('/invoices/{inv}/send',           [InvoicesController::class,'send'])->name('invoices.send');
    Route::post('/invoices/{inv}/remind',         [InvoicesController::class,'sendReminder'])->name('invoices.remind');
    Route::post('/invoices/{inv}/mark-paid',      [InvoicesController::class,'markPaid'])->name('invoices.mark-paid');
    Route::post('/invoices/{inv}/void',           [InvoicesController::class,'void'])->name('invoices.void');
    Route::post('/invoices/{inv}/copy-stripe-id', [InvoicesController::class,'copyStripeId'])->name('invoices.copy-stripe-id');
    // Incidents (new controller)
    Route::get('/incidents',                          [IncidentsController::class,'index'])->name('incidents.index');
    Route::post('/incidents/{inc}/tasks/{task}/complete', [IncidentsController::class,'workTasks'])->name('incidents.work-task');
    Route::post('/incidents/{inc}/escalate-aegis',    [IncidentsController::class,'escalateAegis'])->name('incidents.escalate-aegis');
    Route::post('/incidents/{inc}/updates',            [IncidentsController::class,'addUpdate'])->name('incidents.add-update');
    Route::post('/incidents/{inc}/attachments',        [IncidentsController::class,'attachDoc'])->name('incidents.attach-doc');
    Route::post('/incidents/{inc}/close',              [IncidentsController::class,'close'])->name('incidents.close');
    Route::post('/incidents/{inc}/reopen',             [IncidentsController::class,'reopen'])->name('incidents.reopen');
    // Vault (new methods)
    Route::get('/vault/items/{item}/download',         [VaultController::class,'download'])->name('vault.download');
    Route::post('/vault/items/{item}/reveal',          [VaultController::class,'reveal'])->name('vault.reveal');
    Route::post('/vault/roster/{item}/refer',          [VaultController::class,'referDuringIncident'])->name('vault.refer-during-incident');
    Route::get('/vault/audit-log/export',              [VaultController::class,'exportAuditLog'])->name('vault.export-audit');
    // Providers (new methods)
    Route::post('/providers/{designation}/role-change',[ProvidersController::class,'requestRoleChange'])->name('providers.role-change');
    Route::post('/providers/{designation}/resign',     [ProvidersController::class,'resign'])->name('providers.resign');
    Route::post('/providers/{designation}/re-attest',  [ProvidersController::class,'reAttest'])->name('providers.re-attest');
    Route::post('/providers/{designation}/certify',    [ProvidersController::class,'certify'])->name('providers.certify');
    Route::post('/providers/{practitioner}/refer',     [ProvidersController::class,'sendReferral'])->name('providers.refer');
    // Tasks (new methods)
    Route::post('/tasks/{task}/complete',              [TasksController::class,'complete'])->name('tasks.complete');
    Route::post('/tasks/{task}/note',                  [TasksController::class,'addNote'])->name('tasks.note');
    Route::post('/tasks/{task}/extension',             [TasksController::class,'requestExtension'])->name('tasks.extension');
    Route::post('/tasks/{task}/flag',                  [TasksController::class,'flag'])->name('tasks.flag');
    Route::post('/tasks/{task}/clear-exception',       [TasksController::class,'clearException'])->name('tasks.clear-exception');
    Route::post('/tasks/annual-reattest',              [TasksController::class,'annualReattest'])->name('tasks.annual-reattest');
    // Documents (new methods)
    Route::post('/documents',                          [DocumentsController::class,'upload'])->name('documents.upload');
    Route::post('/documents/{doc}/countersign',        [DocumentsController::class,'countersign'])->name('documents.countersign');
    Route::post('/documents/{doc}/decline',            [DocumentsController::class,'decline'])->name('documents.decline');
    // Finances (new methods)
    Route::post('/finances/connect-stripe',            [FinancesController::class,'connectStripe'])->name('finances.connect-stripe');
    Route::patch('/finances/payment-model',            [FinancesController::class,'setPaymentModel'])->name('finances.payment-model');
    // Settings (new methods)
    Route::post('/settings/notifications',             [SettingsController::class,'updateNotifications'])->name('settings.notifications');
    Route::post('/settings/security/password',         [SettingsController::class,'changePassword'])->name('settings.password');
    Route::post('/settings/security/revoke-all',       [SettingsController::class,'revokeAllSessions'])->name('settings.revoke-all');
    Route::post('/settings/data/export',               [SettingsController::class,'exportData'])->name('settings.export');
    Route::post('/settings/pause',                     [SettingsController::class,'pause'])->name('settings.pause');
    Route::post('/settings/close-account',             [SettingsController::class,'closeAccount'])->name('settings.close-account');
    Route::post('/settings/visibility',                [SettingsController::class,'updateVisibility'])->name('settings.visibility');
    Route::post('/settings/upgrade',                   [SettingsController::class,'upgradeTier'])->name('settings.upgrade');
    // Support (new controller)
    Route::get('/support',                             [SupportController::class,'index'])->name('support.index');
    Route::post('/support/tickets',                    [SupportController::class,'storeTicket'])->name('support.tickets.store');
    Route::post('/support/tickets/{t}/reply',          [SupportController::class,'replyTicket'])->name('support.tickets.reply');
    Route::post('/support/tickets/{t}/close',          [SupportController::class,'closeTicket'])->name('support.tickets.close');
    Route::post('/support/feedback',                   [SupportController::class,'submitFeedback'])->name('support.feedback');
    Route::post('/support/feedback-fab',               [SupportController::class,'submitFeedbackFab'])->name('support.feedback-fab');
    Route::get('/support/help',                        [SupportController::class,'helpArticles'])->name('support.help');
});
```

### Support Steward

```php
Route::middleware(['auth','role:support_steward'])->prefix('ss')->name('ss.')->group(function () {
    // Tasks (new)
    Route::post('/tasks/{task}/complete',          [TasksController::class,'complete'])->name('tasks.complete');
    Route::post('/tasks/{task}/note',              [TasksController::class,'addNote'])->name('tasks.note');
    Route::post('/tasks/{task}/extension',         [TasksController::class,'requestExtension'])->name('tasks.extension');
    Route::post('/tasks/{task}/escalate-cs',       [TasksController::class,'escalateToCs'])->name('tasks.escalate-cs');
    Route::post('/tasks/certify-plan',             [TasksController::class,'certifyPlan'])->name('tasks.certify');
    Route::post('/tasks/acknowledge',              [TasksController::class,'acknowledgePlan'])->name('tasks.acknowledge');
    // Incidents (new methods)
    Route::post('/incidents/{inc}/attachments',    [CriticalIncidentController::class,'attachDoc'])->name('incidents.attach-doc');
    Route::post('/incidents/{inc}/withdraw',       [CriticalIncidentController::class,'withdraw'])->name('incidents.withdraw');
    // Providers (new methods)
    Route::post('/providers/{practitioner}/add-business-cs',    [ProvidersController::class,'addBusinessCs'])->name('providers.add-business-cs');
    Route::post('/providers/{practitioner}/note',                [ProvidersController::class,'saveNote'])->name('providers.note');
    Route::post('/providers/{practitioner}/notify-unresponsive', [ProvidersController::class,'notifyUnresponsive'])->name('providers.notify-unresponsive');
    // Documents (new methods)
    Route::get('/documents/{doc}/download',        [DocumentsController::class,'download'])->name('documents.download');
    Route::post('/documents',                      [DocumentsController::class,'upload'])->name('documents.upload');
    // Settings (new controller)
    Route::post('/settings/notifications',         [SettingsController::class,'updateNotifications'])->name('settings.notifications');
    Route::post('/settings/security/password',     [SettingsController::class,'changePassword'])->name('settings.password');
    Route::post('/settings/security/verify-email', [SettingsController::class,'verifyEmailChange'])->name('settings.verify-email');
    Route::post('/settings/pause',                 [SettingsController::class,'pause'])->name('settings.pause');
    Route::post('/settings/data/export',           [SettingsController::class,'exportData'])->name('settings.export');
    Route::post('/settings/close-account',         [SettingsController::class,'closeAccount'])->name('settings.close-account');
    Route::post('/settings/visibility',            [SettingsController::class,'updateContactVisibility'])->name('settings.visibility');
    Route::post('/settings/theme',                 [SettingsController::class,'applyTheme'])->name('settings.theme');
    Route::post('/settings/delegate',              [SettingsController::class,'grantDelegate'])->name('settings.delegate');
    // Support (new controller)
    Route::post('/support/tickets',                [SupportController::class,'storeTicket'])->name('support.tickets.store');
    Route::post('/support/tickets/{t}/reply',      [SupportController::class,'replyTicket'])->name('support.tickets.reply');
    Route::post('/support/feedback',               [SupportController::class,'submitFeedback'])->name('support.feedback');
    Route::post('/support/feedback-fab',           [SupportController::class,'submitFeedbackFab'])->name('support.feedback-fab');
    Route::get('/support/help',                    [SupportController::class,'helpArticles'])->name('support.help');
});
```

### Business Partner

```php
Route::middleware(['auth','role:business_partner'])->prefix('bp')->name('bp.')->group(function () {
    // Profile (new)
    Route::post('/profile/company',             [ProfileController::class,'completeCompany'])->name('profile.company');
    Route::post('/profile/certifications',      [ProfileController::class,'updateCertifications'])->name('profile.certifications');
    Route::post('/profile/coverage',            [ProfileController::class,'updateCoverage'])->name('profile.coverage');
    Route::post('/profile/specializations',     [ProfileController::class,'updateSpecializations'])->name('profile.specializations');
    Route::post('/profile/portfolio',           [ProfileController::class,'updatePortfolio'])->name('profile.portfolio');
    Route::post('/profile/visibility',          [ProfileController::class,'toggleNetworkVisibility'])->name('profile.visibility');
    Route::post('/profile/revenue-visibility',  [ProfileController::class,'toggleRevenuePrivacy'])->name('profile.revenue-visibility');
    // Proposals (new)
    Route::post('/proposals',                   [ProposalsController::class,'store'])->name('proposals.store');
    Route::patch('/proposals/{prop}',           [ProposalsController::class,'update'])->name('proposals.update');
    Route::post('/proposals/{prop}/withdraw',   [ProposalsController::class,'withdraw'])->name('proposals.withdraw');
    // Contracts (new)
    Route::post('/contracts/{ctr}/cancel',      [ContractsController::class,'cancel'])->name('contracts.cancel');
    Route::post('/contracts/{ctr}/pause',       [ContractsController::class,'pause'])->name('contracts.pause');
    Route::post('/contracts/{ctr}/resume',      [ContractsController::class,'resume'])->name('contracts.resume');
    // Milestones (new)
    Route::post('/contracts/{ctr}/milestones',  [MilestonesController::class,'store'])->name('milestones.store');
    Route::post('/milestones/{ms}/withdraw',    [MilestonesController::class,'withdraw'])->name('milestones.withdraw');
    // Invoices (new)
    Route::post('/invoices/{inv}/resend',       [InvoicesController::class,'resend'])->name('invoices.resend');
    Route::post('/invoices/{inv}/remind',       [InvoicesController::class,'sendReminder'])->name('invoices.remind');
    Route::post('/invoices/{inv}/void',         [InvoicesController::class,'void'])->name('invoices.void');
    Route::post('/invoices/{inv}/refund',       [InvoicesController::class,'refund'])->name('invoices.refund');
    Route::post('/invoices/{inv}/mark-paid',    [InvoicesController::class,'markPaid'])->name('invoices.mark-paid');
    Route::post('/invoices/{inv}/lines',        [InvoicesController::class,'addLineItem'])->name('invoices.lines');
    Route::patch('/invoices/{inv}/draft',       [InvoicesController::class,'updateDraft'])->name('invoices.draft');
    // Tax documents (new controller)
    Route::get('/tax-documents',                [TaxDocumentsController::class,'index'])->name('tax-documents.index');
    Route::get('/tax-documents/{doc}/download', [TaxDocumentsController::class,'download'])->name('tax-documents.download');
    Route::post('/tax-documents/address',       [TaxDocumentsController::class,'updateAddress'])->name('tax-documents.address');
    Route::post('/tax-documents/w9',            [TaxDocumentsController::class,'uploadW9'])->name('tax-documents.w9');
    // Payment setup (new)
    Route::post('/payment-setup/connect',       [PaymentSetupController::class,'connect'])->name('payment-setup.connect');
    Route::get('/payment-setup/export',         [PaymentSetupController::class,'exportStripeData'])->name('payment-setup.export');
    Route::post('/payment-setup/disconnect',    [PaymentSetupController::class,'disconnect'])->name('payment-setup.disconnect');
    // Team (new)
    Route::delete('/team/members/{m}',          [TeamController::class,'removeMember'])->name('team.remove-member');
    Route::patch('/team/members/{m}/status',    [TeamController::class,'setStatus'])->name('team.set-status');
    // Settings (new controller)
    Route::post('/settings/notifications',         [SettingsController::class,'updateNotifications'])->name('settings.notifications');
    Route::post('/settings/security/password',     [SettingsController::class,'changePassword'])->name('settings.password');
    Route::post('/settings/security/verify-email', [SettingsController::class,'verifyEmailChange'])->name('settings.verify-email');
    Route::post('/settings/close-account',         [SettingsController::class,'closeAccount'])->name('settings.close-account');
    Route::post('/settings/pause',                 [SettingsController::class,'pause'])->name('settings.pause');
    Route::post('/settings/transfer',              [SettingsController::class,'transfer'])->name('settings.transfer');
    Route::post('/settings/cancel-subscription',   [SettingsController::class,'cancelSubscription'])->name('settings.cancel-subscription');
    Route::post('/settings/rates-visibility',      [SettingsController::class,'updateRatesVisibility'])->name('settings.rates-visibility');
    Route::post('/settings/data/export',           [SettingsController::class,'exportData'])->name('settings.export');
    Route::post('/settings/theme',                 [SettingsController::class,'applyTheme'])->name('settings.theme');
    // Support (new controller)
    Route::post('/support/tickets',           [SupportController::class,'storeTicket'])->name('support.tickets.store');
    Route::post('/support/tickets/{t}/reply', [SupportController::class,'replyTicket'])->name('support.tickets.reply');
    Route::post('/support/feedback',          [SupportController::class,'submitFeedback'])->name('support.feedback');
    Route::post('/support/feedback-fab',      [SupportController::class,'submitFeedbackFab'])->name('support.feedback-fab');
    Route::get('/support/help',               [SupportController::class,'helpArticles'])->name('support.help');
});

// Public routes used by BP team invitation
Route::post('/invite/team/{token}', [TeamController::class,'acceptInvitation'])->name('public.team.accept');
```

### Admin

```php
Route::middleware(['auth','role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Packages (new methods)
    Route::post('/packages/{pkg}/price',         [PackagesController::class,'updatePrice'])->name('packages.price');
    Route::post('/packages/{pkg}/feature-flag',  [PackagesController::class,'toggleFeatureFlag'])->name('packages.feature-flag');
    Route::post('/packages/{pkg}/limits',        [PackagesController::class,'setLimits'])->name('packages.limits');
    // Users (new)
    Route::post('/users/{u}/impersonate',        [UsersController::class,'impersonate'])->name('users.impersonate');
    // Roles (new)
    Route::post('/roles',                        [RolesController::class,'storeCustomRole'])->name('roles.store');
    Route::post('/roles/{r}/permissions',        [RolesController::class,'setPermissions'])->name('roles.permissions');
    Route::delete('/roles/{r}',                  [RolesController::class,'deleteCustomRole'])->name('roles.delete');
    // Payouts (new controller)
    Route::get('/payouts/pending',               [PayoutsController::class,'index'])->name('payouts.index');
    Route::post('/payouts/{p}/release',          [PayoutsController::class,'releaseManually'])->name('payouts.release');
    // Help articles (new controller)
    Route::get('/help-articles',                 [HelpArticlesController::class,'index'])->name('help-articles.index');
    Route::post('/help-articles',                [HelpArticlesController::class,'store'])->name('help-articles.store');
    Route::patch('/help-articles/{a}',           [HelpArticlesController::class,'update'])->name('help-articles.update');
    Route::post('/help-articles/{a}/publish',    [HelpArticlesController::class,'publish'])->name('help-articles.publish');
    Route::post('/help-articles/reorder',        [HelpArticlesController::class,'reorder'])->name('help-articles.reorder');
    // Complaints (new)
    Route::post('/complaints/{c}/status',        [ComplaintsController::class,'changeStatus'])->name('complaints.status');
    Route::post('/complaints/{c}/escalate',      [ComplaintsController::class,'escalate'])->name('complaints.escalate');
});
```

### Auth

```php
Route::middleware('guest')->group(function () {
    Route::get('/onboarding/role',         [OnboardingController::class,'showRolePicker'])->name('onboarding.role');
    Route::post('/onboarding/role',        [OnboardingController::class,'storeRole'])->name('onboarding.role.store');
    Route::get('/onboarding/intent',       [OnboardingController::class,'showIntent'])->name('onboarding.intent');
    Route::post('/onboarding/intent',      [OnboardingController::class,'storeIntent'])->name('onboarding.intent.store');
    Route::post('/onboarding/maat-addon',  [OnboardingController::class,'selectMaatAddon'])->name('onboarding.maat');
    Route::get('/verify-email/{token}',    [VerifyEmailController::class,'verify'])->name('verify-email');
    Route::post('/verify-email/resend',    [VerifyEmailController::class,'resend'])->name('verify-email.resend');
});
```

### Shared (Messages — new methods)

```php
Route::middleware(['auth'])->prefix('{portal}')->group(function () {
    Route::post('/messages/threads/{t}/pin',    [MessagesController::class,'pinThread'])->name('messages.pin');
    Route::post('/messages/threads/{t}/mute',   [MessagesController::class,'muteThread'])->name('messages.mute');
    Route::post('/messages/threads/{t}/unread', [MessagesController::class,'markUnread'])->name('messages.unread');
});
```



### News + Activity-read + Admin-incidents + Public-SS (UC closure pass)

```php
// Provider news + events
Route::middleware(['auth','role:practitioner'])->prefix('provider')->name('provider.')->group(function () {
    Route::get('/news',                                  [NewsController::class,'index'])->name('news.index');
    Route::post('/news/posts',                           [NewsController::class,'storePost'])->name('news.posts.store');
    Route::patch('/news/posts/{post}',                   [NewsController::class,'updatePost'])->name('news.posts.update');
    Route::delete('/news/posts/{post}',                  [NewsController::class,'deletePost'])->name('news.posts.delete');
    Route::post('/news/posts/{post}/comments',           [NewsController::class,'comment'])->name('news.comment');
    Route::delete('/news/comments/{comment}',            [NewsController::class,'deleteComment'])->name('news.comments.delete');
    Route::post('/news/posts/{post}/reactions',          [NewsController::class,'react'])->name('news.react');
    Route::post('/news/posts/{post}/poll-vote',          [NewsController::class,'votePoll'])->name('news.poll-vote');
    Route::get('/events',                                [NewsController::class,'events'])->name('events.index');
    Route::post('/events/{event}/rsvp',                  [NewsController::class,'rsvp'])->name('events.rsvp');
    Route::get('/news/library',                          [NewsController::class,'library'])->name('news.library');
    Route::get('/news/trending',                         [NewsController::class,'trending'])->name('news.trending');
});

// Cross-portal activity read (any authenticated user, any portal)
Route::middleware(['auth'])->prefix('{portal}')->group(function () {
    Route::post('/activity/{event}/read',                [ActivityController::class,'markRead'])->name('activity.read');
    Route::post('/activity/mark-all-read',               [ActivityController::class,'markAllRead'])->name('activity.read-all');
});

// CS check-in (UC-CS-015)
Route::middleware(['auth','role:continuity_steward'])->prefix('cs')->name('cs.')->group(function () {
    Route::post('/providers/{practitioner}/checkin',     [ProvidersController::class,'addCheckin'])->name('providers.checkin');
});

// Provider service-request decline + session complete (UC-PRV-187, UC-PRV-188)
Route::middleware(['auth','role:practitioner'])->prefix('provider')->name('provider.')->group(function () {
    Route::post('/services/requests/{req}/decline',      [ServicesController::class,'declineRequest'])->name('services.decline-request');
    Route::post('/services/sessions/{session}/complete', [ServicesController::class,'completeSession'])->name('services.complete-session');
});

// Admin payments + complaints priority + incidents view + BP-contract reassign (UC-ADM-064, 068, 070-074; UC-BP-047, 048)
Route::middleware(['auth','role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/incidents',                             [IncidentsController::class,'index'])->name('incidents.index');
    Route::post('/complaints/{c}/priority',              [ComplaintsController::class,'changePriority'])->name('complaints.priority');
    Route::get('/payments',                              [PaymentsController::class,'index'])->name('payments.index');
    Route::get('/payments/{payment}',                    [PaymentsController::class,'show'])->name('payments.show');
    Route::post('/payments/{payment}/refund',            [PaymentsController::class,'refund'])->name('payments.refund');
    Route::get('/payments/snapshot',                     [PaymentsController::class,'stripeSnapshot'])->name('payments.snapshot');
    Route::get('/payments/failed',                       [PaymentsController::class,'failed'])->name('payments.failed');
});

// BP contract reassign (UC-BP-048)
Route::middleware(['auth','role:business_partner'])->prefix('bp')->name('bp.')->group(function () {
    Route::post('/contracts/{ctr}/reassign',             [ContractsController::class,'reassign'])->name('contracts.reassign');
});

// Public SS profile (UC-SS-003) — relationship-gated, requires auth but is "public" in the sense of being viewable across portals
Route::middleware(['auth'])->get('/profile/ss/{slug}',   [PublicProfileController::class,'showSs'])->name('public.ss-profile');
```


# Section 11 — Seeders

All seeders under `database/seeders/`. All seed data mirrors `data/seed.json` from the legacy PHP repo. `DatabaseSeeder::run()` orchestrates in FK-safe order.

---

### `database/seeders/DatabaseSeeder.php`

26 seeders total — full demo dataset across 65 of 71 schema tables. The 6 skipped tables are transient/auth artefacts (`mfa_tokens`, `password_reset_tokens`, `user_sessions`, `bp_team_invitations`, `profile_edit_authorizations`) or dropped by migration 072 (`ss_provider_checkins`).

```php
public function run(): void
{
    $this->call([
        // Layer 1 — identity (no FKs)
        RoleSeeder::class,             // 6 system roles + role_permissions
        UserSeeder::class,             // 17 demo users + user_roles + user_preferences
        UserMetaSeeder::class,         // notify_* gate keys + profile meta

        // Layer 2 — plans + stewards
        PlanSeeder::class,             // 5 continuity_plans + plan_meta
        StewardSeeder::class,          // plan_stewards designations
        PlanTaskSeeder::class,         // plan_tasks per plan
        IncidentConfigSeeder::class,   // plan_incident_configs (3 default + 4 opt-in per plan)

        // Layer 3 — incidents
        IncidentSeeder::class,         // critical_incidents + incident_tasks + incident_meta + incident_updates

        // Layer 4 — vault (depends on incidents for access log)
        VaultSeeder::class,            // vault_items + vault_item_meta + vault_access_log

        // Layer 5 — documents
        DocumentSeeder::class,         // continuity_documents + document_signatures

        // Layer 6 — network
        NetworkSeeder::class,          // network_connections + network_requests + shadow_connections
        ReferralSeeder::class,         // referrals + referral_meta

        // Layer 7 — messaging
        MessageSeeder::class,          // message_threads + messages

        // Layer 8 — services + CEUs
        ServiceSeeder::class,          // services + service_requests + service_sessions
        CeuSeeder::class,              // ceu_entries

        // Layer 9 — BP ecosystem
        BpSeeder::class,               // bp_jobs + bp_proposals + bp_contracts + contract_meta + bp_milestones + bp_team_members + bp_saved_jobs
        InvoiceSeeder::class,          // bp_invoices + bp_invoice_line_items + bp_invoice_payments + cs_invoices + practitioner_payment_methods
        PayoutSeeder::class,           // bp_payouts + cs_payouts + bp_tax_documents + practitioner_payments

        // Layer 10 — provider check-ins (CS proactive + SS provider, writes to `provider_checkins` post-rename)
        ProviderCheckinSeeder::class,  // provider_checkins + ss_provider_notes

        // Layer 11 — news
        NewsSeeder::class,             // news_posts + news_comments + news_reactions + news_poll_votes + news_trending_topics + news_library_items + news_events

        // Layer 12 — support
        SupportSeeder::class,          // complaints + complaint_meta + complaint_replies + help_articles

        // Layer 13 — admin + system
        AdminSeeder::class,            // admin_audit_log + stripe_webhook_events
        PackageSeeder::class,          // package_overrides

        // Layer 14 — activity fan-out (references all above)
        ActivitySeeder::class,         // activity_events (83 rows, all 14 event types, cross-portal fan-out)

        // Layer 15 — activity read receipts (must run after ActivitySeeder)
        ActivityReadSeeder::class,     // activity_event_reads
    ]);
}
```

---

### `database/seeders/UserSeeder.php`
Creates seeded users matching the canonical demo IDs from `data/seed.json`:
- `p_sarah` — Practitioner, Practice tier, services_mode=1, public
- `cs_marcus` — Business CS, cs_account_type=business, stripe_connected=1, verified=1
- `cs_alternate` / `cs_priya` — Invited CS variants (`invited=true` URL flag)
- `ss_linda` — Support Steward, invited_by=p_sarah, public
- `bp_acme` — BP Agency, bp_type=agency, bp_business_name="Acme Practice Services"
- `bp_jamal` — BP Freelancer, bp_type=freelancer
- `admin_root` — Admin

Each user gets:
- `user_meta` defaults (26 `notify_*` keys, all `true` opt-out)
- `user_preferences` row (theme=gold, timezone=America/New_York)
- `user_roles` row with `is_default=1` matching `users.role`

---

### `database/seeders/PlanSeeder.php`
Creates Sarah's continuity plan:
- 1 `continuity_plans` row, status=`active`, `signed_at` set, `vault_attested_at` set, `annual_review_date`=now+335d
- 3 default `plan_incident_configs` enabled (Death, Incapacitation, Extended Absence)
- 4 opt-in `plan_incident_configs` disabled
- 2 `plan_stewards` rows — Marcus (Primary CS, active), Priya (Alternate CS, active)
- 1 `plan_stewards` row — Linda (SS, active)
- 8 `plan_tasks` rows assigned to CS, 4 to SS

---

### `database/seeders/IncidentSeeder.php`
Creates 2 incidents for Sarah's plan:
- 1 active incident (status=`active`, type=`incapacitation`, reported_by=ss_linda, verified_by=cs_marcus) + 9 `incident_tasks` (5 complete, 4 pending) — unsealsVault
- 1 closed incident (status=`closed`, historical) + closure update

Toggle the active one with `?emergency=true|false` query param at demo time.

---

### `database/seeders/VaultSeeder.php`
Creates ~20 vault items for Sarah across 4 zones (mirrors `seed.json` `vault_items`):
- **Credentials** (zone=credentials): SimplePractice, Bank, Office Ally, Google Workspace, Squarespace — each AES-256-GCM enveloped
- **Roster** (zone=roster): A.M., B.K., several others with priority + notes
- **Documents** (zone=documents): SOP, transfer instructions, BAA copies
- **Instructions** (zone=instructions): emergency runbook, client transfer protocol

---

### `database/seeders/NetworkSeeder.php`
- ~8 active `network_connections` for Sarah (mix of practitioners + BPs)
- ~3 `shadow_connections` (off-platform contacts)
- 1 pending `network_requests` row

---

### `database/seeders/BpSeeder.php`
- 6 `bp_jobs` posted by Sarah (mix of open, paused, filled)
- 10 `bp_proposals` from various BPs (mix of pending, under_review, accepted, declined)
- 2 `bp_contracts` active (Sarah↔Acme, Sarah↔Jamal)
- ~8 `bp_milestones` across contracts (mix of pending, submitted, approved, paid)
- 4 `bp_invoices` (mix of sent, paid, overdue)
- 2 `bp_team_members` for Acme (agency team)
- 1 `bp_team_invitations` pending

---

### `database/seeders/NewsSeeder.php`
- ~6 `news_posts` (mix of post, poll, announcement types)
- ~10 `news_events` (upcoming + past)
- ~5 `news_trending_topics`
- ~10 `news_library_items`

---

### `database/seeders/SupportSeeder.php`
- 3 sample `complaints` for p_sarah (1 open support ticket, 1 resolved feedback, 1 in_progress complaint)
- 5 `complaint_replies` across them (mix of submitter + admin, some internal)
- ~15 `help_articles` covering common how-to topics

---

### `database/seeders/AdminSeeder.php`
- 5 system `roles` (system_admin, support_agent, finance_admin, content_admin, read_only)
- ~25 `role_permissions` rows
- `admin_root` user with `system_admin` role assigned

---

### `database/seeders/PackageSeeder.php`
- 4 `package_overrides` rows for tier definitions (Access, Practice, Services add-on, MAAT add-on) — matches the pricing snapshot in `_shared/pricing_data.php`
- effective_at = now, expires_at = null (current pricing)

---

### `database/seeders/RoleSeeder.php`
- 6 `roles` rows: `role_admin`, `role_practitioner`, `role_cs`, `role_ss`, `role_bp`, `role_cs_reviewer` (last is non-system)
- ~23 `role_permissions` rows across admin and CS-reviewer roles (permission_key + granted flag)
- Note: `user_roles` rows for the demo users are inserted by `UserSeeder` (FK requires users to exist first)

---

### `database/seeders/UserMetaSeeder.php`
- ~26 `notify_*` boolean gate keys per user (opt-out defaults — all enabled)
- Profile meta: `practitioner_specialties` (JSON), `availability_status`, `accepting_status`, `intent_segment`, `w9_last4`, `ssn_last4` for relevant users
- Idempotent — uses UQ on `(user_id, meta_key)`

---

### `database/seeders/StewardSeeder.php`
- `plan_stewards` rows for every demo plan
- Sarah's plan: Marcus (Primary CS, active), Priya (Alternate CS, active), Linda (SS, active)
- Other plans: Priya/CS-alternate variants, James SS
- Sets `vault_access`, `responsibilities`, `permissions` JSON, lifecycle timestamps

---

### `database/seeders/PlanTaskSeeder.php`
- 8 CS-assigned `plan_tasks` + 4 SS-assigned `plan_tasks` per active plan
- Mix of statuses (pending, in_progress, complete) for demo realism
- Uses `assigned_to` enum (`cs|ss|practitioner`) and `module` grouping

---

### `database/seeders/IncidentConfigSeeder.php`
- 7 `plan_incident_configs` rows per plan (one per `IncidentType`)
- 3 default-enabled (Death, Incapacitation, ExtendedAbsence), 4 opt-in disabled (Missing, Detainment, NaturalDisaster, Geopolitical)
- `authorized_cs_ids`, `authorized_ss_ids`, `docs_required` JSON populated

---

### `database/seeders/DocumentSeeder.php`
- 6 `continuity_documents` for Sarah's plan (steward designation, client notification template, plan amendment, records release authorisation, archived original, pending draft)
- 3 `document_signatures` entries (signer_name + signature_ip required)
- Mix of doc_type and status enum values

---

### `database/seeders/ReferralSeeder.php`
- ~6 `referrals` across the network (Sarah↔Maria, Sarah↔David, BP↔practitioner)
- Status mix: sent, accepted, declined, closed
- `referral_meta` rows for `presenting_issue`, `client_age_range`, etc.

---

### `database/seeders/MessageSeeder.php`
- ~5 `message_threads` (1:1 + group, mix of pinned and archived)
- ~20 `messages` across threads with `attachments` and `reactions` JSON
- `last_message_at` set on threads

---

### `database/seeders/ServiceSeeder.php`
- 8 `services` rows (Sarah and Maria offering individual therapy, couples, intensives, free consultations)
- Mix of `price_type` (session, fixed, inquiry) and `status` (active, inactive)
- 3 `service_requests` (status: new, accepted) — `inquirer_id` + `practitioner_id` + `message`
- 1 `service_sessions` row (scheduled, with `amount_cents`)

---

### `database/seeders/CeuSeeder.php`
- 5 `ceu_entries` across Sarah, Maria, David
- `credit_hours` (decimal allowed only on this column per schema rule), `completed_on`, `expires_on`
- Mix of providers (EMDR Institute, NASW, APA, Gottman Institute)

---

### `database/seeders/InvoiceSeeder.php`
- 5 `bp_invoices` (paid, sent, draft, overdue, void) + 3 `bp_invoice_line_items` + 2 `bp_invoice_payments`
- 3 `cs_invoices` (paid, sent, overdue)
- 2 `practitioner_payment_methods` (Sarah Visa, Maria Mastercard) with Stripe `pm_*` references

---

### `database/seeders/PayoutSeeder.php`
- 1 `bp_payouts` row (paid) with Stripe `tr_*` reference
- 1 `bp_tax_documents` row (1099 available for prior year)
- 1 `cs_payouts` row (paid)
- 1 `practitioner_payments` row (cs_fee kind, paid status)

---

### `database/seeders/ProviderCheckinSeeder.php`
**Writes to `provider_checkins`** (renamed from `ss_provider_checkins` by migration 072)
- 10 SS check-ins for Sarah and Maria (mix of `ok`, `concern`, `unreachable` statuses)
- 6 CS proactive check-ins (`steward_type=cs`)
- 3 `ss_provider_notes` entries

---

### `database/seeders/ActivitySeeder.php`
- 83 `activity_events` rows generated programmatically across all 15 demo users
- All 14 valid `event_type` values used (account, attestation, compliance, document, incident, message, news, payment, plan, referral, service, steward, system, task, vault)
- Severity mix with at least one `critical` event per active incident
- Cross-portal fan-out: e.g. incident reports create rows for practitioner + CS + SS

---

### `database/seeders/ActivityReadSeeder.php`
- For each of the 11 primary demo users, marks the oldest 60% of their own activity events as read
- Writes `activity_event_reads` row per (user_id, activity_event_id) pair
- `read_at` randomized within a realistic window after `created_at`

---

# Section 12 — Config + Packages

---

## Required Composer packages

```jsonc
// composer.json additions
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^11.0",
    "laravel/sanctum": "^4.0",
    "laravel/cashier": "^15.0",
    "laravel/horizon": "^5.21",
    "laravel/reverb": "^1.0",
    "inertiajs/inertia-laravel": "^1.0",
    "tightenco/ziggy": "^2.0",
    "league/flysystem-aws-s3-v3": "^3.0",
    "aws/aws-sdk-php": "^3.0",
    "spatie/laravel-permission": "^6.0",
    "pragmarx/google2fa-laravel": "^2.1",
    "intervention/image": "^3.0"
  },
  "require-dev": {
    "laravel/pint": "^1.13",
    "laravel/telescope": "^5.0",
    "pestphp/pest": "^2.0",
    "pestphp/pest-plugin-laravel": "^2.0",
    "barryvdh/laravel-debugbar": "^3.9",
    "fakerphp/faker": "^1.23"
  }
}
```

---

## `config/services.php` additions

```php
return [
    // ... existing services

    'stripe' => [
        'key'            => env('STRIPE_KEY'),
        'secret'         => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'connect_client_id' => env('STRIPE_CONNECT_CLIENT_ID'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    's3' => [
        'key'      => env('AWS_ACCESS_KEY_ID'),
        'secret'   => env('AWS_SECRET_ACCESS_KEY'),
        'region'   => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'bucket'   => env('AWS_BUCKET'),
        'url'      => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
    ],

    'keeper' => [
        'api_url'    => env('KEEPER_API_URL'),
        'public_key' => env('KEEPER_PUBLIC_KEY'),
        // Phase-2 integration — current build uses local AES-256-GCM envelope
    ],

    'google_analytics' => [
        'measurement_id' => env('GA_MEASUREMENT_ID'),
        'phi_exclude'    => true,
    ],
];
```

---

## `config/auth.php`

```php
return [
    'defaults' => [
        'guard'     => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],
        'sanctum' => [
            'driver'   => 'sanctum',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\User::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];
```

---

## `config/horizon.php` queue setup

```php
'environments' => [
    'production' => [
        'supervisor-incident' => [
            'connection'   => 'redis',
            'queue'        => ['incident'],
            'balance'      => 'simple',
            'processes'    => 10,
            'tries'        => 3,
            'timeout'      => 60,
        ],
        'supervisor-default' => [
            'connection'   => 'redis',
            'queue'        => ['default'],
            'balance'      => 'auto',
            'minProcesses' => 2,
            'maxProcesses' => 10,
            'tries'        => 3,
            'timeout'      => 120,
        ],
        'supervisor-email' => [
            'connection'   => 'redis',
            'queue'        => ['email'],
            'balance'      => 'auto',
            'minProcesses' => 1,
            'maxProcesses' => 5,
            'tries'        => 5,
            'timeout'      => 60,
        ],
        'supervisor-digest' => [
            'connection'   => 'redis',
            'queue'        => ['digest'],
            'processes'    => 2,
            'tries'        => 2,
            'timeout'      => 300,
        ],
    ],
],
```

---

## `app/Console/Kernel.php` scheduled tasks

```php
protected function schedule(Schedule $schedule): void
{
    // Daily — fire AnnualReviewDue events for plans approaching review window
    $schedule->job(new AnnualReviewReminderJob())
        ->dailyAt('09:00')
        ->name('annual-review-reminder')
        ->withoutOverlapping();

    // Every 5 minutes — re-seal vault if active incident closed
    $schedule->job(new VaultSealCheckJob())
        ->everyFiveMinutes()
        ->name('vault-seal-check')
        ->withoutOverlapping();

    // Hourly — escalate incidents stuck in 'verified' >24h
    $schedule->job(new StaleIncidentAlertJob())
        ->hourly()
        ->name('stale-incident-alert')
        ->withoutOverlapping();

    // Weekly Mondays at 08:00 — dispatch digest emails per user
    $schedule->command('aegis:dispatch-digests weekly')
        ->weeklyOn(1, '08:00')
        ->name('weekly-digest');

    // Daily — mark BP invoices overdue
    $schedule->command('aegis:sweep-overdue-invoices')
        ->dailyAt('03:00')
        ->name('overdue-invoice-sweep');

    // Daily — expire continuity plans where annual_review missed
    $schedule->command('aegis:expire-stale-plans')
        ->dailyAt('02:00')
        ->name('expire-stale-plans');
}
```

---

## `config/aegis.php` (custom)

```php
return [
    'demo_hosts' => [
        'localhost:8000',
        'aegis.devlet.tech',
    ],

    'vault' => [
        'encryption_version'  => 1,    // AES-256-GCM envelope version byte
        'reveal_audit_required' => true,
    ],

    'notifications' => [
        'gate_default'    => true,     // opt-out (gates default ON)
        'ungated_events'  => [
            'incident_reported',
            'incident_verified',
            'incident_activated',
            'vault_unsealed',
            'incident_escalated',
            'password_reset',
            'contract_generated',
            'contract_signed',
            'invoice_sent',
            'invoice_paid',
            'account_locked',
            'role_changed',
        ],
    ],

    'plan' => [
        'expiry_days'         => 365,
        'review_window_days'  => 30,
        'review_checklist_count' => 8,
    ],

    'incident' => [
        'stale_threshold_hours' => 24,
        'types_optin' => ['missing', 'detainment', 'natural_disaster', 'geopolitical'],
    ],
];
```

---

## `.env` template

```bash
APP_NAME=Aegis
APP_ENV=production
APP_KEY=
APP_URL=https://aegis.devlet.tech

DB_CONNECTION=mysql
DB_HOST=
DB_DATABASE=aegis
DB_USERNAME=
DB_PASSWORD=

QUEUE_CONNECTION=redis
REDIS_HOST=
REDIS_PASSWORD=

# Mail (SES)
MAIL_MAILER=ses
MAIL_FROM_ADDRESS=noreply@aegis.devlet.tech
MAIL_FROM_NAME="${APP_NAME}"
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1

# Storage (S3)
FILESYSTEM_DISK=s3
AWS_BUCKET=aegis-prod

# Stripe
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_CONNECT_CLIENT_ID=ca_...
CASHIER_CURRENCY=USD

# Reverb (real-time messaging)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=

# Sentry / observability
SENTRY_LARAVEL_DSN=
```

---

# Section — Backend Pitfalls (session-hardened)

Rules distilled from real debugging. Each one cost repeated round-trips; honor them up front.

## Meta / sparse-attribute writes
- **Never** `updateOrCreate` with `id` in the payload on any `HasUuids` table (`user_meta`, `plan_meta`, etc.). Use explicit first-or-create; generate the id manually (`'um_' . Str::lower(Str::random(12))`).
- **Never** compare a cast `meta_type` against a literal string after `pluck`. Read via the model's `typed_value` accessor.
- **Always** `$user->load('meta')` (not `loadMissing`) before a read that follows a write in the same request.

## Enums (BackedEnum)
- **Always** resolve `.value` before a `match` statement or a string comparison:
  ```php
  $role = $model->role instanceof \BackedEnum ? $model->role->value : (string) $model->role;
  ```
- Model-cast enum properties come off the model as enum *instances*, not strings — every `->pluck()`, `->firstWhere()`, and array-key use must account for this.

## Config save architecture
- Prefer **one atomic endpoint** that accepts all fields of a multi-panel config over several parallel PUTs. Parallel writes race and leave partial state.
- Load current config in the controller and pass it as a single `xxxConfig` prop so the Vue side can hydrate reactive state from the DB (never hardcode defaults in the component).
- Provide a matching **reset** endpoint that clears meta keys + resets user columns, so the UI reset button persists.

## Shadow / recommendation scoping
- `getRecommendedShadowProviders()` and referral-candidate queries must filter `role === 'practitioner'` on the joined user. CS/SS/BP users can appear in `network_recommendations` rows and must never surface in shadow slots or the referral list.

## Seeders
- On a slug-unique collision during `updateOrInsert`, **rename** the conflicting row's slug (`slug . '-dup-' . substr($id,-6)`) — don't `delete()` it; the row may have FK children (`continuity_plans`, etc.) and the delete will throw a 1451 constraint error.
- `bp_type` accepts only `'agency'` and `'freelancer'`.
- Every prerequisite user an `updateOrInsert` references must exist first, with valid enum columns.

## Connection / request columns
- `network_connections`: `user_id` / `connected_user_id` (bidirectional query: `WHERE user_id = X OR connected_user_id = X`).
- `network_requests`: `requester_id` / `recipient_id`.

## Money
- Integer cents everywhere. Never float, never decimal columns.

## Write-path parity
- Field names in the FormRequest must match the Vue `useForm()` keys exactly.
- Route verb must match the submit verb (`Route::put` → `.put()`).
- Service column names must match the migration (`->string()` vs `->json()` changes how you read the value).

## Ziggy
- After adding a route, run `php artisan ziggy:generate`. Hand-editing `ziggy.js` is a stopgap only; the generated file is source of truth.

---

*End of AEGIS_LARAVEL_STRUCTURE.md*
