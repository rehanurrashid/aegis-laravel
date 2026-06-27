<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IncidentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanIncidentConfig extends Model
{
    use HasFactory;

    protected $table        = 'plan_incident_configs';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'plan_id', 'incident_type', 'is_active',
        'docs_required', 'authorized_ss_ids', 'authorized_cs_ids',
    ];

    protected $casts = [
        'incident_type'     => IncidentType::class,
        'is_active'         => 'boolean',
        'docs_required'     => 'array',
        'authorized_ss_ids' => 'array',
        'authorized_cs_ids' => 'array',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ContinuityPlan::class, 'plan_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', 1);
    }
}
