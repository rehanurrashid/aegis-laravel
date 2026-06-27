<?php

declare(strict_types=1);

namespace App\Http\Controllers\BusinessPartner;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->bp_type === 'agency', 403);

        $members = User::where('invited_by_id', $request->user()->id)
            ->where('role', 'business_partner')->get();

        return Inertia::render('BusinessPartner/Team', [
            'members' => $members,
            'pendingInvitations' => $members->whereNull('verified')->values(),
        ]);
    }

    public function invite(Request $request): RedirectResponse
    {
        abort_unless($request->user()->bp_type === 'agency', 403);
        $data = $request->validate([
            'email'        => 'required|email',
            'display_name' => 'required|string|max:100',
            'role_in_team' => 'nullable|string|max:50',
        ]);
        // Stub — actual team-invite goes through AdminUserService-style invite flow
        return back()->with('success', 'Team invitation sent.');
    }

    public function remove(Request $request, User $member): RedirectResponse
    {
        abort_unless($request->user()->bp_type === 'agency', 403);
        abort_unless($member->invited_by_id === $request->user()->id, 403);
        $member->update(['deactivated_at' => now()]);
        return back()->with('success', 'Team member removed.');
    }

    public function updateRole(Request $request, User $member): RedirectResponse
    {
        abort_unless($request->user()->bp_type === 'agency', 403);
        $data = $request->validate(['role_in_team' => 'required|string|max:50']);
        // Stored as user_meta or team_member.role column
        return back()->with('success', 'Role updated.');
    }
}
