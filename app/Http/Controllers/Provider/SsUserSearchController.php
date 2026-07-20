<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserMeta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Live user search for the "Add Support Steward — Existing User" flow.
 * Returns users who are:
 *   - Support Steward role, OR
 *   - Practitioner who has available_as_ss = '1' in user_meta
 * Excludes the currently authenticated user.
 */
class SsUserSearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q      = trim($request->get('q', ''));
        $authId = auth()->id();

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        // SS role users
        $ssUsers = User::where('role', UserRole::SupportSteward->value)
            ->where('id', '!=', $authId)
            ->where('status', 'active')
            ->where(function ($query) use ($q) {
                $query->where('display_name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
            })
            ->limit(10)
            ->get(['id', 'display_name', 'email', 'credentials', 'avatar_initials', 'role']);

        // Practitioners available as SS
        $ssAvailablePractitioners = User::where('role', UserRole::Practitioner->value)
            ->where('id', '!=', $authId)
            ->where('status', 'active')
            ->where(function ($query) use ($q) {
                $query->where('display_name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
            })
            ->whereHas('meta', fn ($m) =>
                $m->where('meta_key', 'available_as_ss')->where('meta_value', '1')
            )
            ->limit(5)
            ->get(['id', 'display_name', 'email', 'credentials', 'avatar_initials', 'role']);

        $results = $ssUsers->concat($ssAvailablePractitioners)
            ->unique('id')
            ->take(10)
            ->map(fn ($u) => [
                'id'           => $u->id,
                'display_name' => $u->display_name,
                'email'        => $u->email,
                'credentials'  => $u->credentials ?? '',
                'initials'     => $u->avatar_initials
                    ?? collect(explode(' ', $u->display_name))->map(fn ($w) => strtoupper($w[0] ?? ''))->take(2)->implode(''),
                'role_label'   => $u->role === UserRole::SupportSteward ? 'Support Steward' : 'Practitioner (Available as SS)',
            ]);

        return response()->json($results);
    }
}
