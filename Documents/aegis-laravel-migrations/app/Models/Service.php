<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ServicePriceType;
use App\Enums\ServiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'services';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'practitioner_id', 'title', 'description', 'category',
        'price_cents', 'price_type', 'status', 'is_public',
    ];

    protected $casts = [
        'price_type'  => ServicePriceType::class,
        'status'      => ServiceStatus::class,
        'price_cents' => 'integer',
        'is_public'   => 'boolean',
    ];

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function requests(): HasMany { return $this->hasMany(ServiceRequest::class, 'service_id'); }
    public function sessions(): HasMany { return $this->hasMany(ServiceSession::class, 'service_id'); }

    public function scopeActive($q)  { return $q->where('status', ServiceStatus::Active->value); }
    public function scopePublic($q)  { return $q->where('is_public', 1); }
}
