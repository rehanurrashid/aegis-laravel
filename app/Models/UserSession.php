<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    use HasFactory;

    protected $table        = 'user_sessions';
    protected $keyType      = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = [
        'id', 'user_id', 'session_token', 'ip_address', 'user_agent',
        'device_label', 'last_seen_at', 'revoked_at', 'created_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'revoked_at'   => 'datetime',
        'created_at'   => 'datetime',
    ];

    protected $hidden = ['session_token'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($q)
    {
        return $q->whereNull('revoked_at');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}
