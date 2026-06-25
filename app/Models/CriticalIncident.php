<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Enums\IncidentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CriticalIncident extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'critical_incidents';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'plan_id', 'practitioner_id', 'reported_by_id',
        'incident_type', 'status', 'severity',
        'reported_at', 'verified_at', 'verified_by_id',
        'activated_at', 'closed_at', 'summary',
    ];

    protected $casts = [
        'incident_type' => IncidentType::class,
        'status'        => IncidentStatus::class,
        'severity'      => IncidentSeverity::class,
        'reported_at'   => 'datetime',
        'verified_at'   => 'datetime',
        'activated_at'  => 'datetime',
        'closed_at'     => 'datetime',
    ];

    public function plan(): BelongsTo        { return $this->belongsTo(ContinuityPlan::class, 'plan_id'); }
    public function practitioner(): BelongsTo { return $this->belongsTo(User::class, 'practitioner_id'); }
    public function reportedBy(): BelongsTo  { return $this->belongsTo(User::class, 'reported_by_id'); }
    public function verifiedBy(): BelongsTo  { return $this->belongsTo(User::class, 'verified_by_id'); }

    public function meta(): HasMany    { return $this->hasMany(IncidentMeta::class, 'incident_id'); }
    public function tasks(): HasMany   { return $this->hasMany(IncidentTask::class, 'incident_id'); }
    public function updates(): HasMany { return $this->hasMany(IncidentUpdate::class, 'incident_id'); }

    public function scopeActive($q) { return $q->where('status', IncidentStatus::Active->value); }
    public function scopeOpen($q)
    {
        return $q->whereIn('status', [
            IncidentStatus::Reported->value,
            IncidentStatus::Verified->value,
            IncidentStatus::Active->value,
        ]);
    }
    public function scopeForPractitioner($q, string $id) { return $q->where('practitioner_id', $id); }

    public function unsealsVault(): bool
    {
        return $this->status === IncidentStatus::Active;
    }
}
