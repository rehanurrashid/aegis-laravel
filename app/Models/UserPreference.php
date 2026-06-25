<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    use HasFactory;

    protected $table        = 'user_preferences';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'user_id', 'theme', 'font_size', 'compact', 'language',
        'timezone', 'date_format', 'time_format', 'currency',
    ];

    protected $casts = [
        'font_size' => 'integer',
        'compact'   => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
