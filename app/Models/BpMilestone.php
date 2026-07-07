<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MilestoneStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BpMilestone extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'bp_milestones';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'contract_id', 'title', 'description', 'amount_cents', 'status',
        'assigned_member_id', 'due_at', 'submitted_at', 'approved_at', 'paid_at', 'payout_id', 'sort_order',
    ];

    protected $casts = [
        'status'       => MilestoneStatus::class,
        'amount_cents' => 'integer',
        'sort_order'   => 'integer',
        'due_at'       => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at'  => 'datetime',
        'paid_at'      => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(BpContract::class, 'contract_id');
    }

    public function assignedMember(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_member_id');
    }

    public function scopeOpen($q)
    {
        return $q->whereIn('status', [
            MilestoneStatus::Pending->value,
            MilestoneStatus::Submitted->value,
            MilestoneStatus::Approved->value,
        ]);
    }
}
