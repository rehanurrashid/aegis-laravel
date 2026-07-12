<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable audit row per milestone submission cycle.
 * Each submit (including resubmits after revision) creates a new row.
 */
class BpMilestoneSubmission extends Model
{
    protected $table        = 'bp_milestone_submissions';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'milestone_id', 'contract_id', 'submitted_by',
        'submission_notes', 'attachments', 'hours_logged',
        'reviewed_by', 'reviewed_at', 'review_action', 'review_notes',
    ];

    protected $casts = [
        'attachments'  => 'array',
        'hours_logged' => 'decimal:2',
        'reviewed_at'  => 'datetime',
    ];

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(BpMilestone::class, 'milestone_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
