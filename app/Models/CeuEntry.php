<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CeuEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'ceu_entries';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'practitioner_id', 'title', 'provider_name',
        'credit_hours', 'completed_on', 'expires_on', 'certificate_ref',
    ];

    protected $casts = [
        'credit_hours' => 'decimal:2',
        'completed_on' => 'date',
        'expires_on'   => 'date',
    ];

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function scopeActive($q)  { return $q->where('expires_on', '>', now()); }
    public function scopeExpired($q) { return $q->where('expires_on', '<=', now()); }
}
