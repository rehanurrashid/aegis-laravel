<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MfaToken extends Model
{
    use HasFactory;

    protected $table        = 'mfa_tokens';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'user_id', 'secret', 'method', 'recovery_codes',
        'email_otp_hash', 'email_otp_expires_at',
        'confirmed_at', 'disabled_at',
    ];

    protected $casts = [
        'secret'               => 'encrypted',
        'recovery_codes'       => 'array',
        'confirmed_at'         => 'datetime',
        'disabled_at'          => 'datetime',
        'email_otp_expires_at' => 'datetime',
    ];

    protected $hidden = ['secret', 'recovery_codes', 'email_otp_hash'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->confirmed_at !== null && $this->disabled_at === null;
    }
}
