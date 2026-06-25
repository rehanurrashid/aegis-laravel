<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PractitionerPaymentMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'practitioner_payment_methods';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'practitioner_id', 'label', 'brand', 'stripe_pm_id', 'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PractitionerPayment::class, 'payment_method_id');
    }

    public function scopeDefault($q) { return $q->where('is_default', 1); }
}
