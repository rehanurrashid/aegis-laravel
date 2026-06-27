<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaskAssignee;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentTask extends Model
{
    use HasFactory;

    protected $table        = 'incident_tasks';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'incident_id', 'assigned_to_id', 'assigned_role',
        'title', 'description', 'status', 'timeline',
        'completed_at', 'exception_reason', 'sort_order',
    ];

    protected $casts = [
        'assigned_role' => TaskAssignee::class,
        'status'        => TaskStatus::class,
        'completed_at'  => 'datetime',
        'sort_order'    => 'integer',
    ];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(CriticalIncident::class, 'incident_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function scopeOpen($q)
    {
        return $q->whereIn('status', [TaskStatus::Pending->value, TaskStatus::InProgress->value]);
    }

    public function scopeException($q)
    {
        return $q->where('status', TaskStatus::Exception->value);
    }

    public function isException(): bool
    {
        return $this->status === TaskStatus::Exception;
    }
}
