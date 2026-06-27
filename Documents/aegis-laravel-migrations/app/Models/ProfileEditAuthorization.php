<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProfileEditAuthStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfileEditAuthorization extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'profile_edit_authorizations';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'practitioner_id', 'authorized_user_id',
        'scope', 'status', 'granted_at', 'revoked_at',
    ];

    protected $casts = [
        'scope'      => 'array',
        'status'     => ProfileEditAuthStatus::class,
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function authorized(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_user_id');
    }

    public function scopeActive($q)
    {
        return $q->where('status', ProfileEditAuthStatus::Active->value);
    }

    public function isActive(): bool
    {
        return $this->status === ProfileEditAuthStatus::Active && $this->revoked_at === null;
    }
}
