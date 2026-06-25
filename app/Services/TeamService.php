<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Models\BpTeamInvitation;
use App\Models\BpTeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Business Partner team management — agency BPs invite team members,
 * assign permission roles, and update status. Per UC-BP-team-*.
 */
class TeamService
{
    public function __construct(private ActivityService $activity) {}

    public function invite(User $agency, string $email, string $permissionRole = 'specialist', ?string $department = null): BpTeamInvitation
    {
        if ($agency->id === null) {
            throw new RuntimeException('Inviter must be persisted.');
        }

        return BpTeamInvitation::create([
            'id'              => 'ti_' . Str::lower(Str::random(12)),
            'agency_id'       => $agency->id,
            'email'           => $email,
            'permission_role' => $permissionRole,
            'department'      => $department,
            'status'          => 'pending',
            'token'           => Str::random(40),
            'expires_at'      => now()->addDays(14),
            'created_at'      => now(),
        ]);
    }

    public function acceptInvite(BpTeamInvitation $invite, User $member): BpTeamMember
    {
        if ($invite->status !== 'pending') {
            throw new RuntimeException('Invitation is no longer pending.');
        }
        if ($invite->expires_at && $invite->expires_at->isPast()) {
            throw new RuntimeException('Invitation has expired.');
        }

        $invite->update(['status' => 'accepted', 'accepted_at' => now()]);

        $teamMember = BpTeamMember::create([
            'id'              => 'tm_' . Str::lower(Str::random(12)),
            'agency_id'       => $invite->agency_id,
            'member_id'       => $member->id,
            'permission_role' => $invite->permission_role,
            'department'      => $invite->department,
            'status'          => 'active',
            'joined_at'       => now(),
            'created_at'      => now(),
        ]);

        $this->activity->log(
            $invite->agency_id, 'business_partner', 'account', ActivitySeverity::Info,
            'team_member_joined',
            "{$member->display_name} joined your team",
            "Role: {$invite->permission_role}",
            'bp_team_member', $teamMember->id, $member->id
        );

        return $teamMember;
    }

    public function declineInvite(BpTeamInvitation $invite): BpTeamInvitation
    {
        $invite->update(['status' => 'declined', 'declined_at' => now()]);
        return $invite->fresh();
    }

    public function updatePermissions(BpTeamMember $member, string $permissionRole, ?string $department = null): BpTeamMember
    {
        $member->update([
            'permission_role' => $permissionRole,
            'department'      => $department ?? $member->department,
        ]);
        return $member->fresh();
    }

    public function setStatus(BpTeamMember $member, string $status): BpTeamMember
    {
        $member->update(['status' => $status]);
        return $member->fresh();
    }

    public function remove(BpTeamMember $member): bool
    {
        return (bool) $member->delete();
    }

    public function roster(User $agency): Collection
    {
        return BpTeamMember::where('agency_id', $agency->id)
            ->orderBy('joined_at')
            ->get();
    }

    public function pendingInvitations(User $agency): Collection
    {
        return BpTeamInvitation::where('agency_id', $agency->id)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();
    }
}
