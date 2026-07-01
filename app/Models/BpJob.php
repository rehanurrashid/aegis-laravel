<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BpBudgetType;
use App\Enums\BpJobStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BpJob extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'bp_jobs';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'practitioner_id', 'title', 'category', 'description',
        'budget_type', 'budget_amount_cents', 'currency', 'location_pref',
        'status', 'is_urgent', 'proposals_count', 'posted_at', 'closes_at',
        'job_type', 'experience_level', 'partner_type_pref', 'tags', 'certifications',
        'requires_hipaa', 'requires_nda', 'requires_baa', 'application_deadline',
        'max_applicants', 'payment_method', 'billing_frequency', 'perks',
        'is_featured', 'internal_notes', 'start_date',
    ];

    protected $casts = [
        'budget_type'          => BpBudgetType::class,
        'status'               => BpJobStatus::class,
        'budget_amount_cents'  => 'integer',
        'proposals_count'      => 'integer',
        'is_urgent'            => 'boolean',
        'posted_at'            => 'datetime',
        'closes_at'            => 'datetime',
        'tags'                 => 'array',
        'requires_hipaa'       => 'boolean',
        'requires_nda'         => 'boolean',
        'requires_baa'         => 'boolean',
        'application_deadline' => 'date',
        'max_applicants'       => 'integer',
        'is_featured'          => 'boolean',
        'start_date'           => 'date',
    ];

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function proposals(): HasMany { return $this->hasMany(BpProposal::class, 'job_id'); }
    public function contracts(): HasMany { return $this->hasMany(BpContract::class, 'job_id'); }
    public function savedBy(): HasMany   { return $this->hasMany(BpSavedJob::class, 'job_id'); }

    public function scopeOpen($q)   { return $q->where('status', BpJobStatus::Open->value); }
    public function scopeUrgent($q) { return $q->where('is_urgent', 1); }
}
