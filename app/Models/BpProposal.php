<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BpBudgetType;
use App\Enums\ProposalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class BpProposal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'bp_proposals';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'job_id', 'bp_id', 'cover_letter',
        'proposed_rate_cents', 'proposed_rate_type', 'status',
        'submitted_at', 'responded_at', 'pipeline_stage', 'decline_reason',
        'internal_notes', 'interview_type', 'interview_at',
    ];

    protected $casts = [
        'proposed_rate_type'  => BpBudgetType::class,
        'status'              => ProposalStatus::class,
        'proposed_rate_cents' => 'integer',
        'submitted_at'        => 'datetime',
        'responded_at'        => 'datetime',
        'interview_at'        => 'datetime',
    ];

    public function job(): BelongsTo { return $this->belongsTo(BpJob::class, 'job_id'); }
    public function bp(): BelongsTo  { return $this->belongsTo(User::class, 'bp_id'); }

    public function contract(): HasOne
    {
        return $this->hasOne(BpContract::class, 'proposal_id');
    }

    public function scopePending($q)  { return $q->where('status', ProposalStatus::Pending->value); }
    public function scopeAccepted($q) { return $q->where('status', ProposalStatus::Accepted->value); }
}
