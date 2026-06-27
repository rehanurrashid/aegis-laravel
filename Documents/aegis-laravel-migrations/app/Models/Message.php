<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'messages';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'thread_id', 'sender_id', 'recipient_id', 'body',
        'attachments', 'reactions', 'read_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'reactions'   => 'array',
        'read_at'     => 'datetime',
    ];

    public function thread(): BelongsTo    { return $this->belongsTo(MessageThread::class, 'thread_id'); }
    public function sender(): BelongsTo    { return $this->belongsTo(User::class, 'sender_id'); }
    public function recipient(): BelongsTo { return $this->belongsTo(User::class, 'recipient_id'); }

    public function scopeUnread($q) { return $q->whereNull('read_at'); }

    public function isRead(): bool { return $this->read_at !== null; }
}
