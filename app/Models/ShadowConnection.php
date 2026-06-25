<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShadowConnection extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'shadow_connections';
    protected $keyType      = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = ['id', 'user_id', 'shadow_user_id', 'shadow_name', 'source', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function owner(): BelongsTo      { return $this->belongsTo(User::class, 'user_id'); }
    public function shadowUser(): BelongsTo { return $this->belongsTo(User::class, 'shadow_user_id'); }
}
