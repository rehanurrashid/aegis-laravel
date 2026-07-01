<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageThreadBlock extends Model
{
    public    $timestamps   = false;
    protected $table        = 'message_thread_blocks';
    protected $fillable     = ['thread_id', 'blocker_id', 'blocked_id', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function thread():  BelongsTo { return $this->belongsTo(MessageThread::class, 'thread_id'); }
    public function blocker(): BelongsTo { return $this->belongsTo(User::class, 'blocker_id'); }
    public function blocked(): BelongsTo { return $this->belongsTo(User::class, 'blocked_id'); }
}
