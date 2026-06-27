<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolePermission extends Model
{
    use HasFactory;

    protected $table        = 'role_permissions';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = ['id', 'role_id', 'permission_key', 'granted'];

    protected $casts = [
        'granted' => 'boolean',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function scopeGranted($q) { return $q->where('granted', 1); }
}
