<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContractStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BpContract extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'bp_contracts';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'job_id', 'proposal_id', 'practitioner_id', 'bp_id', 'title',
        'status', 'total_value_cents',
        'signed_at', 'cancelled_at', 'cancel_reason', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'status'            => ContractStatus::class,
        'total_value_cents' => 'integer',
        'signed_at'         => 'datetime',
        'cancelled_at'      => 'datetime',
        'started_at'        => 'datetime',
        'completed_at'      => 'datetime',
    ];

    public function job(): BelongsTo          { return $this->belongsTo(BpJob::class, 'job_id'); }
    public function proposal(): BelongsTo     { return $this->belongsTo(BpProposal::class, 'proposal_id'); }
    public function practitioner(): BelongsTo { return $this->belongsTo(User::class, 'practitioner_id'); }
    public function bp(): BelongsTo           { return $this->belongsTo(User::class, 'bp_id'); }

    public function meta(): HasMany       { return $this->hasMany(ContractMeta::class, 'contract_id'); }
    public function milestones(): HasMany { return $this->hasMany(BpMilestone::class, 'contract_id'); }
    public function invoices(): HasMany   { return $this->hasMany(BpInvoice::class, 'contract_id'); }

    public function scopeActive($q) { return $q->where('status', ContractStatus::Active->value); }
}
