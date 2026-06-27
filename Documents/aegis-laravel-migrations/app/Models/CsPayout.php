<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PayoutStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CsPayout extends Model
{
    use HasFactory;

    protected $table        = 'cs_payouts';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'cs_id', 'amount_cents', 'currency', 'status',
        'description', 'stripe_payout_id', 'scheduled_at', 'paid_at',
    ];

    protected $casts = [
        'status'       => PayoutStatus::class,
        'amount_cents' => 'integer',
        'scheduled_at' => 'datetime',
        'paid_at'      => 'datetime',
    ];

    public function cs(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cs_id');
    }

    public function scopePaid($q) { return $q->where('status', PayoutStatus::Paid->value); }
}
