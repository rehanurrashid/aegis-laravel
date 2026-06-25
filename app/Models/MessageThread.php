<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageThread extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'message_threads';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'subject', 'created_by_id', 'last_message_at', 'is_pinned', 'is_muted',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'is_pinned'       => 'boolean',
        'is_muted'        => 'boolean',
    ];

    public function creator(): BelongsTo  { return $this->belongsTo(User::class, 'created_by_id'); }
    public function messages(): HasMany   { return $this->hasMany(Message::class, 'thread_id'); }

    public function scopePinned($q) { return $q->where('is_pinned', 1); }
}
