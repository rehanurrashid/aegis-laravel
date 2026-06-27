<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MetaType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralMeta extends Model
{
    use HasFactory;

    protected $table        = 'referral_meta';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = ['id', 'referral_id', 'meta_key', 'meta_value', 'meta_type'];

    protected $casts = [
        'meta_type' => MetaType::class,
    ];

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class, 'referral_id');
    }
}
