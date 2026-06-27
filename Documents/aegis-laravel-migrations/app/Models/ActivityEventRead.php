<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-user read tracking for activity_events.
 * Table: activity_event_reads.
 */
class ActivityEventRead extends Model
{
    use HasFactory;

    protected $table        = 'activity_event_reads';
    protected $keyType      = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = ['id', 'user_id', 'activity_event_id', 'read_at', 'created_at'];

    protected $casts = [
        'read_at'    => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(ActivityEvent::class, 'activity_event_id');
    }
}
