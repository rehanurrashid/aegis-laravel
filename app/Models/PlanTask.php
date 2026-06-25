<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaskAssignee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'plan_tasks';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'plan_id', 'assigned_to', 'steward_id', 'title', 'timeline', 'sort_order',
    ];

    protected $casts = [
        'assigned_to' => TaskAssignee::class,
        'sort_order'  => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ContinuityPlan::class, 'plan_id');
    }

    public function steward(): BelongsTo
    {
        return $this->belongsTo(User::class, 'steward_id');
    }

    public function scopeForCs($q)
    {
        return $q->where('assigned_to', TaskAssignee::ContinuitySteward->value);
    }

    public function scopeForSs($q)
    {
        return $q->where('assigned_to', TaskAssignee::SupportSteward->value);
    }

    public function scopeOrdered($q)
    {
        return $q->orderBy('sort_order');
    }
}
