<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserKnownDevice extends Model
{
    public    $timestamps   = false;
    protected $table        = 'user_known_devices';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'user_id', 'fingerprint', 'device_label',
        'location_label', 'ip', 'first_seen_at', 'last_seen_at',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
