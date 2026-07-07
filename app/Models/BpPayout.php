<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PayoutStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpPayout extends Model
{
    use HasFactory;

    protected $table        = 'bp_payouts';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'bp_id', 'provider_id', 'contract_id', 'milestone_id', 'amount_cents', 'currency', 'status',
        'description', 'stripe_payout_id', 'stripe_transfer_id', 'stripe_payment_intent_id', 'scheduled_at', 'paid_at', 'released_at',
    ];

    protected $casts = [
        'status'       => PayoutStatus::class,
        'amount_cents' => 'integer',
        'scheduled_at' => 'datetime',
        'paid_at'      => 'datetime',
        'released_at'  => 'datetime',
    ];

    public function bp(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bp_id');
    }

    public function scopePaid($q) { return $q->where('status', PayoutStatus::Paid->value); }
}
