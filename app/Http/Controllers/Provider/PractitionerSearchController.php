<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Enums\UserRole;
use App\Models\User;
use App\Models\NetworkConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PractitionerSearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q      = trim($request->get('q', ''));
        $authId = auth()->id();

        $practitioners = User::where('role', UserRole::Practitioner)
            ->where('id', '!=', $authId)
            ->where(function ($query) use ($q) {
                if ($q !== '') {
                    $query->where('display_name', 'like', "%{$q}%")
                          ->orWhere('email', 'like', "%{$q}%");
                }
            })
            ->limit(20)
            ->get(['id', 'display_name', 'slug', 'avatar_url', 'avatar_initials'])
            ->map(function ($p) use ($authId) {
                $connected = NetworkConnection::where(function ($q) use ($authId, $p) {
                    $q->where('requester_id', $authId)->where('receiver_id', $p->id);
                })->orWhere(function ($q) use ($authId, $p) {
                    $q->where('requester_id', $p->id)->where('receiver_id', $authId);
                })->where('status', 'accepted')->exists();

                return [
                    'id'           => $p->id,
                    'display_name' => $p->display_name,
                    'slug'         => $p->slug,
                    'avatar_url'   => $p->avatar_url,
                    'initials'     => $p->avatar_initials,
                    'is_connected' => $connected,
                ];
            });

        return response()->json($practitioners);
    }
}
