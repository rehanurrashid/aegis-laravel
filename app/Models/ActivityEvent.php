<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityEventType;
use App\Enums\ActivityPortal;
use App\Enums\ActivitySeverity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityEvent extends Model
{
    use HasFactory;

    protected $table        = 'activity_events';
    protected $keyType      = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = [
        'id', 'user_id', 'portal', 'event_type', 'entry_type', 'actor_id',
        'severity', 'module', 'action', 'title', 'description',
        'linkable_type', 'linkable_id', 'scoped_provider_id',
        'read_at', 'created_at',
    ];

    protected $casts = [
        'portal'     => ActivityPortal::class,
        'event_type' => ActivityEventType::class,
        'severity'   => ActivitySeverity::class,
        'read_at'    => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function scopedProvider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scoped_provider_id');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reads(): HasMany
    {
        return $this->hasMany(ActivityEventRead::class, 'activity_event_id');
    }

    public function scopeUnread($q)      { return $q->whereNull('read_at'); }
    public function scopeForPortal($q, ActivityPortal $p) { return $q->where('portal', $p->value); }
    public function scopeOfModule($q, string $module)     { return $q->where('module', $module); }
    public function scopeOfType($q, ActivityEventType $t) { return $q->where('event_type', $t->value); }
    public function scopeLogs($q)          { return $q->where('entry_type', 'log'); }
    public function scopeNotifications($q) { return $q->where('entry_type', 'notification'); }
    public function scopeOfEntryType($q, string $entryType) { return $q->where('entry_type', $entryType); }
}
