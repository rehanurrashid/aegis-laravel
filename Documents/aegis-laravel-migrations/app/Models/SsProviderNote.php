<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SsProviderNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'ss_provider_notes';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = ['id', 'ss_id', 'practitioner_id', 'body'];

    public function ss(): BelongsTo           { return $this->belongsTo(User::class, 'ss_id'); }
    public function practitioner(): BelongsTo { return $this->belongsTo(User::class, 'practitioner_id'); }
}
