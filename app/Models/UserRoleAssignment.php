<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Multi-role pivot. Model class is UserRoleAssignment to avoid collision
 * with App\Enums\UserRole. DB table remains `user_roles`.
 */
class UserRoleAssignment extends Model
{
    use HasFactory;

    protected $table        = 'user_roles';
    protected $keyType      = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = ['id', 'user_id', 'role', 'is_default', 'enabled_at'];

    protected $casts = [
        'role'       => UserRole::class,
        'is_default' => 'boolean',
        'enabled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeDefault($q)
    {
        return $q->where('is_default', 1);
    }
}
