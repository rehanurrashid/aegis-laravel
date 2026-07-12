<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MilestoneStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BpMilestone extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'bp_milestones';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'contract_id', 'title', 'description', 'amount_cents', 'status',
        'assigned_member_id', 'due_at', 'sort_order',
        // Submission timestamps
        'submitted_at', 'approved_at', 'paid_at', 'payout_id',
        // Escrow (added Wave 1 migration)
        'escrow_intent_id', 'escrow_charge_id', 'transfer_id',
        'funded_at', 'funded_cents',
        'released_at', 'released_cents',
        'refunded_at', 'refunded_cents', 'refund_stripe_id',
        'auto_release_at', 'revision_count',
        'rejection_reason', 'revision_notes',
        'reminder_sent_at',   // Wave 7: tracks when review reminder email was sent
    ];

    protected $casts = [
        'status'         => MilestoneStatus::class,
        'amount_cents'   => 'integer',
        'funded_cents'   => 'integer',
        'released_cents' => 'integer',
        'refunded_cents' => 'integer',
        'sort_order'     => 'integer',
        'revision_count' => 'integer',
        'due_at'         => 'datetime',
        'submitted_at'   => 'datetime',
        'approved_at'    => 'datetime',
        'paid_at'        => 'datetime',
        'funded_at'      => 'datetime',
        'released_at'    => 'datetime',
        'refunded_at'    => 'datetime',
        'auto_release_at'=> 'datetime',
        'reminder_sent_at'=> 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function contract(): BelongsTo
    {
        return $this->belongsTo(BpContract::class, 'contract_id');
    }

    public function assignedMember(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_member_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(BpMilestoneSubmission::class, 'milestone_id');
    }

    /** Most recent submission (for quick access to review notes / attachments). */
    public function latestSubmission(): HasMany
    {
        return $this->hasMany(BpMilestoneSubmission::class, 'milestone_id')
                    ->latest()
                    ->limit(1);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeOpen($q)
    {
        return $q->whereIn('status', [
            MilestoneStatus::Pending->value,
            MilestoneStatus::Funded->value,
            MilestoneStatus::InProgress->value,
            MilestoneStatus::Submitted->value,
            MilestoneStatus::Approved->value,
        ]);
    }

    public function scopePendingReview($q)
    {
        return $q->where('status', MilestoneStatus::Submitted->value);
    }

    public function scopeAutoReleaseDue($q)
    {
        return $q->where('status', MilestoneStatus::Submitted->value)
                 ->whereNotNull('auto_release_at')
                 ->where('auto_release_at', '<=', now());
    }

    // ── Computed helpers ─────────────────────────────────────────────────────

    public function isEscrowHeld(): bool
    {
        $status = $this->status instanceof MilestoneStatus
            ? $this->status
            : MilestoneStatus::tryFrom((string) $this->status);

        return $status?->isEscrowHeld() ?? false;
    }

    public function isSubmittable(): bool
    {
        $status = $this->status instanceof MilestoneStatus
            ? $this->status
            : MilestoneStatus::tryFrom((string) $this->status);

        return $status?->isSubmittable() ?? false;
    }
}
