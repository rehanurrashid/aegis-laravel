<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SendEmailJob;

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

        $invitation = BpTeamInvitation::create([
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

        SendEmailJob::dispatch(
            'emails.bp.41-team-invite',
            [
                'invitation_id' => $invitation->id,
                'agency_name'   => $agency->display_name,
                'invite_token'  => $invitation->token,
                'ungated'       => true,
            ],
            null,
            $email
        );

        return $invitation;
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

        // Notify agency (notification)
        $this->activity->log(
            $invite->agency_id, 'business_partner', 'account', ActivitySeverity::Info,
            'team_member_joined',
            "{$member->display_name} joined your team",
            "Role: {$invite->permission_role}",
            BpTeamMember::class, $teamMember->id, $member->id, 'notification', $member->id
        );
        // Actor log for the member
        $this->activity->log(
            $member->id, 'business_partner', 'account', ActivitySeverity::Info,
            'team_invite_accepted', 'Joined a Business Partner team',
            "You joined {$invite->agency?->display_name ?? 'an agency'} as {$invite->permission_role}.",
            BpTeamMember::class, $teamMember->id, $invite->agency_id, 'log', $member->id
        );

        return $teamMember;
    }

    public function declineInvite(BpTeamInvitation $invite): BpTeamInvitation
    {
        $invite->update(['status' => 'declined', 'declined_at' => now()]);
        // Notify agency
        $this->activity->log(
            $invite->agency_id, 'business_partner', 'account', ActivitySeverity::Info,
            'team_invite_declined',
            "Team invite declined",
            "The invitation to {$invite->email} was declined.",
            BpTeamInvitation::class, $invite->id, null, 'notification', null
        );
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
        $this->activity->log(
            $member->agency_id, 'business_partner', 'account', ActivitySeverity::Info,
            'team_member_removed', 'Team member removed',
            "A team member has been removed from your team.",
            BpTeamMember::class, $member->id, $member->member_id, 'log', $member->agency_id
        );
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
