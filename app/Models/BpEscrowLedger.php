<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable audit ledger for every escrow money movement.
 * Never updated — only inserted. One row per Stripe operation.
 */
class BpEscrowLedger extends Model
{
    protected $table        = 'bp_escrow_ledger';
    protected $keyType      = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;   // created_at only, no updated_at

    protected $fillable = [
        'id', 'contract_id', 'milestone_id', 'kind',
        'amount_cents', 'provider_id', 'bp_id',
        'stripe_object_id', 'stripe_object_type',
        'description', 'actor_id', 'created_at',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'created_at'   => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(BpContract::class, 'contract_id');
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(BpMilestone::class, 'milestone_id');
    }
}
