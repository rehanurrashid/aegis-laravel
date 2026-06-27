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
        $user = $request->user();
        $connections = $this->network->getConnections($user->id);
        $bpCount = NetworkConnection::where(function ($q) use ($user) {
                $q->where('user_a_id', $user->id)->orWhere('user_b_id', $user->id);
            })->count();

        return Inertia::render('Provider/Network', [
            'connections'        => $connections,
            'pendingRequests'    => $this->network->getPendingRequests($user->id),
            'shadowConnections'  => $this->network->getShadowConnections($user->id),
            'stats'              => ['total_connections' => $connections->count(), 'bp_count' => $bpCount],
        ]);
    }

    public function connect(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'note'       => 'nullable|string|max:500',
        ]);
        $this->network->sendRequest($request->user(), User::findOrFail($data['to_user_id']), $data['note'] ?? null);
        return back()->with('success', 'Request sent.');
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
        abort_if($connection->user_a_id !== $request->user()->id && $connection->user_b_id !== $request->user()->id, 403);
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
        $this->network->inviteExternal($request->user(), $data['email'], $data['display_name'], $data['note'] ?? null);
        return back()->with('success', 'Invitation sent.');
    }
}
