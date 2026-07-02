<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\NetworkConnection;
use App\Models\NetworkRequest as NetworkRequestModel;
use App\Models\User;
use App\Services\NetworkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NetworkController extends Controller
{
    public function __construct(private NetworkService $network) {}

    public function index(Request $request): Response
    {
        $user        = $request->user();
        $connections = $this->network->getConnections($user->id);

        $serializeConn = function (NetworkConnection $nc) use ($user) {
            $partner = ($nc->user_id === $user->id) ? $nc->target : $nc->owner;
            if (!$partner) return null;
            $type = $nc->connection_type instanceof \BackedEnum
                ? $nc->connection_type->value
                : (string) $nc->connection_type;
            return [
                'id'               => $nc->id,
                'connection_type'  => $type,
                'connected_at'     => $nc->connected_at?->toISOString(),
                'partner_id'       => $partner->id,
                'partner_name'     => $partner->display_name
                    . ($partner->credentials ? ', ' . $partner->credentials : ''),
                'partner_initials' => $partner->avatar_initials
                    ?? strtoupper(substr($partner->display_name, 0, 2)),
                'partner_role'     => $partner->title ?? '',
                'partner_location' => $partner->location ?? '',
                'partner_slug'     => $partner->slug ?? '',
                'partner_specialty'=> $partner->specialty ?? '',
            ];
        };

        $connType = fn($c) => $c->connection_type instanceof \BackedEnum
            ? $c->connection_type->value : (string)$c->connection_type;

        $clinical = $connections
            ->filter(fn($c) => in_array($connType($c), ['practitioner', 'clinical']))
            ->values()->map($serializeConn)->filter()->values();

        $business = $connections
            ->filter(fn($c) => in_array($connType($c), ['business_partner']))
            ->values()->map($serializeConn)->filter()->values();

        $pending = $this->network->getPendingRequests($user->id)
            ->map(function (NetworkRequestModel $nr) {
                $req = $nr->requester;
                if (!$req) return null;
                return [
                    'id'                 => $nr->id,
                    'request_type'       => 'clinical',
                    'message'            => $nr->message,
                    'created_at'         => $nr->created_at?->toISOString(),
                    'requester_id'       => $req->id,
                    'requester_name'     => $req->display_name
                        . ($req->credentials ? ', ' . $req->credentials : ''),
                    'requester_initials' => $req->avatar_initials
                        ?? strtoupper(substr($req->display_name, 0, 2)),
                    'requester_role'     => $req->title ?? '',
                    'requester_location' => $req->location ?? '',
                    'requester_slug'     => $req->slug ?? '',
                ];
            })->filter()->values();

        $shadows = $this->network->getShadowConnections($user->id)
            ->map(function ($sc) {
                $shadow = $sc->shadowUser;
                return [
                    'id'               => $sc->id,
                    'shadow_name'      => $shadow?->display_name ?? $sc->shadow_name ?? '',
                    'shadow_initials'  => $shadow?->avatar_initials
                        ?? strtoupper(substr($sc->shadow_name ?? 'SC', 0, 2)),
                    'shadow_role'      => $shadow?->title ?? '',
                    'shadow_location'  => $shadow?->location ?? '',
                    'shadow_specialty' => $shadow?->specialty ?? '',
                    'shadow_user_id'   => $sc->shadow_user_id,
                    'shadow_slug'      => $shadow?->slug ?? '',
                ];
            })->values();

        // Serialized for ReferralModal :network prop
        $referralNetwork = $clinical->map(fn($c) => [
            'id'           => $c['partner_id'],
            'display_name' => $c['partner_name'],
            'credentials'  => '',
            'slug'         => $c['partner_slug'],
            'specialty'    => $c['partner_specialty'],
            'location'     => $c['partner_location'],
        ])->values();

        return Inertia::render('provider/Network', [
            'clinicalConnections' => $clinical,
            'bpConnections'       => $business,
            'pendingRequests'     => $pending,
            'shadowConnections'   => $shadows,
            'referralNetwork'     => $referralNetwork,
            'roster'              => [],
            'stats'               => [
                'clinical'         => $clinical->count(),
                'bp_count'         => $business->count(),
                'total_refs'       => 0,
                'avg_acc'          => 0,
                'avg_resp'         => 0,
                'pending_requests' => $pending->count(),
                'active_shadows'   => $shadows->count(),
            ],
        ]);
    }

    public function connect(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'note'       => 'nullable|string|max:500',
        ]);
        $this->network->sendRequest(
            $request->user(),
            User::findOrFail($data['to_user_id']),
            $data['note'] ?? null
        );
        return back()->with('success', 'Connection request sent.');
    }

    public function accept(Request $request, NetworkRequestModel $networkRequest): RedirectResponse
    {
        $this->network->acceptRequest($networkRequest, $request->user());
        return back()->with('success', 'Connection accepted.');
    }

    public function decline(Request $request, NetworkRequestModel $networkRequest): RedirectResponse
    {
        $this->network->declineRequest($networkRequest, $request->user(), $request->input('reason'));
        return back()->with('success', 'Request declined.');
    }

    public function disconnect(Request $request, NetworkConnection $connection): RedirectResponse
    {
        abort_if(
            $connection->user_id !== $request->user()->id
                && $connection->connected_user_id !== $request->user()->id,
            403
        );
        $this->network->disconnect($connection, $request->user());
        return back()->with('success', 'Disconnected.');
    }

    public function invite(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email'        => 'required|email',
            'display_name' => 'required|string|max:100',
            'note'         => 'nullable|string|max:500',
        ]);
        $this->network->inviteExternal(
            $request->user(),
            $data['email'],
            $data['display_name'],
            $data['note'] ?? null
        );
        return back()->with('success', 'Invitation sent.');
    }
}
