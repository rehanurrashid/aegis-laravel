<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CeuRequirement extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'ceu_requirements';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'user_id', 'jurisdiction', 'total_hours', 'cycle', 'due_date', 'required_types',
    ];

    protected $casts = [
        'due_date'    => 'date',
        'total_hours' => 'integer',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
