<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MetaType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintMeta extends Model
{
    use HasFactory;

    protected $table        = 'complaint_meta';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = ['id', 'complaint_id', 'meta_key', 'meta_value', 'meta_type'];

    protected $casts = [
        'meta_type' => MetaType::class,
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class, 'complaint_id');
    }
}
