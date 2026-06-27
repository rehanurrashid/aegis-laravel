<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IncidentStatus;
use App\Enums\PlanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContinuityPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'continuity_plans';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'practitioner_id', 'status', 'plan_version',
        'signed_at', 'signature_name', 'signature_title', 'signature_ip',
        'expires_at', 'annual_review_date', 'last_review_at', 'annual_review_notes',
        'vault_attested_at', 'vault_attestation_note',
    ];

    protected $casts = [
        'status'             => PlanStatus::class,
        'plan_version'       => 'integer',
        'signed_at'          => 'datetime',
        'expires_at'         => 'datetime',
        'annual_review_date' => 'datetime',
        'last_review_at'     => 'datetime',
        'vault_attested_at'  => 'datetime',
    ];

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function meta(): HasMany
    {
        return $this->hasMany(PlanMeta::class, 'plan_id');
    }

    public function stewards(): HasMany
    {
        return $this->hasMany(PlanSteward::class, 'plan_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(PlanTask::class, 'plan_id');
    }

    public function incidentConfigs(): HasMany
    {
        return $this->hasMany(PlanIncidentConfig::class, 'plan_id');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(CriticalIncident::class, 'plan_id');
    }

    public function activeIncident(): HasOne
    {
        return $this->hasOne(CriticalIncident::class, 'plan_id')
            ->where('status', IncidentStatus::Active->value);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ContinuityDocument::class, 'plan_id');
    }

    public function scopeActive($q)
    {
        return $q->where('status', PlanStatus::Active->value);
    }

    public function scopeReviewDueSoon($q, int $days = 30)
    {
        return $q->where('annual_review_date', '<=', now()->addDays($days));
    }

    public function scopeForPractitioner($q, string $id)
    {
        return $q->where('practitioner_id', $id);
    }

    public function isLive(): bool
    {
        return $this->status?->isLive() ?? false;
    }
}
