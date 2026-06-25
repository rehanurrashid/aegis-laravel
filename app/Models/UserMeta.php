<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MetaType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMeta extends Model
{
    use HasFactory;

    protected $table        = 'user_meta';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = ['id', 'user_id', 'meta_key', 'meta_value', 'meta_type'];

    protected $casts = [
        'meta_type' => MetaType::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypedValueAttribute(): mixed
    {
        return match ($this->meta_type) {
            MetaType::Int       => (int) $this->meta_value,
            MetaType::Boolean   => filter_var($this->meta_value, FILTER_VALIDATE_BOOLEAN),
            MetaType::Json      => json_decode((string) $this->meta_value, true),
            MetaType::Timestamp => $this->meta_value ? \Carbon\Carbon::parse($this->meta_value) : null,
            default             => $this->meta_value,
        };
    }
}
