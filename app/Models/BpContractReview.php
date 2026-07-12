<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Post-completion review submitted by either party.
 * Enforced: one review per (contract_id, reviewer_id) via unique constraint.
 */
class BpContractReview extends Model
{
    protected $table        = 'bp_contract_reviews';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'contract_id', 'reviewer_id', 'reviewee_id',
        'rating', 'communication', 'quality', 'timeliness',
        'review_text', 'is_public', 'review_dismissed',
    ];

    protected $casts = [
        'rating'           => 'integer',
        'communication'    => 'integer',
        'quality'          => 'integer',
        'timeliness'       => 'integer',
        'is_public'        => 'boolean',
        'review_dismissed' => 'boolean',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(BpContract::class, 'contract_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }
}
