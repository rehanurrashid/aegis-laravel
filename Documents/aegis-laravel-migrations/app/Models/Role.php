<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'roles';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = ['id', 'name', 'system_role', 'description'];

    protected $casts = [
        'system_role' => 'boolean',
    ];

    public function permissions(): HasMany
    {
        return $this->hasMany(RolePermission::class, 'role_id');
    }

    public function scopeCustom($q) { return $q->where('system_role', 0); }
    public function scopeSystem($q) { return $q->where('system_role', 1); }
}
