<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReferralStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Referral extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'referrals';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'sender_id', 'recipient_id', 'status', 'subject', 'responded_at', 'closed_at',
    ];

    protected $casts = [
        'status'       => ReferralStatus::class,
        'responded_at' => 'datetime',
        'closed_at'    => 'datetime',
    ];

    public function sender(): BelongsTo    { return $this->belongsTo(User::class, 'sender_id'); }
    public function recipient(): BelongsTo { return $this->belongsTo(User::class, 'recipient_id'); }

    public function meta(): HasMany { return $this->hasMany(ReferralMeta::class, 'referral_id'); }

    public function scopeOpen($q)
    {
        return $q->whereIn('status', [ReferralStatus::Sent->value, ReferralStatus::Accepted->value]);
    }

    public function scopeForUser($q, string $id)
    {
        return $q->where(function ($w) use ($id) {
            $w->where('sender_id', $id)->orWhere('recipient_id', $id);
        });
    }
}
