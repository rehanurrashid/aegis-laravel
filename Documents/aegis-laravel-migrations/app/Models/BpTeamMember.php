<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeamMemberStatus;
use App\Enums\TeamPermissionRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BpTeamMember extends Model
{
    use HasFactory, SoftDeletes;

    protected $table        = 'bp_team_members';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'agency_id', 'member_id', 'permission_role',
        'department', 'status', 'joined_at',
    ];

    protected $casts = [
        'permission_role' => TeamPermissionRole::class,
        'status'          => TeamMemberStatus::class,
        'joined_at'       => 'datetime',
    ];

    public function agency(): BelongsTo { return $this->belongsTo(User::class, 'agency_id'); }
    public function member(): BelongsTo { return $this->belongsTo(User::class, 'member_id'); }

    public function scopeActive($q) { return $q->where('status', TeamMemberStatus::Active->value); }
}
