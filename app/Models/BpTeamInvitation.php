<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TeamInvitationStatus;
use App\Enums\TeamPermissionRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpTeamInvitation extends Model
{
    use HasFactory;

    protected $table        = 'bp_team_invitations';
    protected $keyType      = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = [
        'id', 'agency_id', 'invitee_email', 'permission_role',
        'status', 'invite_token', 'expires_at', 'responded_at', 'created_at',
    ];

    protected $casts = [
        'permission_role' => TeamPermissionRole::class,
        'status'          => TeamInvitationStatus::class,
        'expires_at'      => 'datetime',
        'responded_at'    => 'datetime',
        'created_at'      => 'datetime',
    ];

    protected $hidden = ['invite_token'];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agency_id');
    }

    public function isPending(): bool { return $this->status === TeamInvitationStatus::Pending; }
    public function isExpired(): bool { return $this->expires_at && $this->expires_at->isPast(); }
}
