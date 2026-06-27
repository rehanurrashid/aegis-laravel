<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\Network\ConnectionAccepted;

use App\Enums\ActivitySeverity;
use App\Jobs\SendEmailJob;
use App\Models\NetworkConnection;
use App\Models\NetworkRequest;
use App\Models\ShadowConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class NetworkService
{
    public function __construct(private ActivityService $activity) {}

    public function sendRequest(User $from, User $to, ?string $note = null): NetworkRequest
    {
        if ($from->id === $to->id) {
            throw new RuntimeException('Cannot send a network request to yourself.');
        }
        if ($this->isConnected($from->id, $to->id)) {
            throw new RuntimeException('Already connected.');
        }
        if (NetworkRequest::where('from_user_id', $from->id)
            ->where('to_user_id', $to->id)
            ->where('status', 'pending')->exists()) {
            throw new RuntimeException('A pending request already exists.');
        }

        $req = NetworkRequest::create([
            'id'           => 'nr_' . Str::lower(Str::random(12)),
            'from_user_id' => $from->id,
            'to_user_id'   => $to->id,
            'note'         => $note,
            'status'       => 'pending',
            'created_at'   => now(),
        ]);

        $this->activity->log(
            $to->id,
            $this->portalFor($to->role),
            'account',
            ActivitySeverity::Info,
            'network_request_received',
            "{$from->display_name} sent you a network connection request",
            $note ? Str::limit($note, 140) : 'Tap to review and accept or decline.',
            'network_request',
            $req->id,
            $from->id
        );

        return $req;
    }

    public function acceptRequest(NetworkRequest $req, User $accepter): NetworkConnection
    {
        if ($req->to_user_id !== $accepter->id) {
            throw new RuntimeException('Only the recipient can accept.');
        }
        if ($req->status !== 'pending') {
            throw new RuntimeException('Request is not pending.');
        }

        return DB::transaction(function () use ($req, $accepter) {
            $req->update(['status' => 'accepted', 'responded_at' => now()]);

            $conn = NetworkConnection::create([
                'id'           => 'nc_' . Str::lower(Str::random(12)),
                'user_a_id'    => $req->from_user_id,
                'user_b_id'    => $req->to_user_id,
                'connected_at' => now(),
            ]);

            $from = User::find($req->from_user_id);
            $this->activity->log(
                $from->id,
                $this->portalFor($from->role),
                'account',
                ActivitySeverity::Info,
                'network_request_accepted',
                "{$accepter->display_name} accepted your network request",
                'You are now connected.',
                'network_connection',
                $conn->id,
                $accepter->id
            );

            event(new ConnectionAccepted($conn, $accepter));

            return $conn;
        });
    }

    public function declineRequest(NetworkRequest $req, User $decliner, ?string $reason = null): NetworkRequest
    {
        if ($req->to_user_id !== $decliner->id) {
            throw new RuntimeException('Only the recipient can decline.');
        }
        $req->update([
            'status' => 'declined',
            'responded_at' => now(),
            'decline_reason' => $reason,
        ]);

        return $req->fresh();
    }

    public function disconnect(NetworkConnection $conn, User $actor): void
    {
        $conn->delete();

        $otherId = $conn->user_a_id === $actor->id ? $conn->user_b_id : $conn->user_a_id;
        $other = User::find($otherId);

        $this->activity->log(
            $other->id,
            $this->portalFor($other->role),
            'account',
            ActivitySeverity::Info,
            'network_disconnected',
            "{$actor->display_name} disconnected from your network",
            'You are no longer connected.',
            'user',
            $actor->id,
            $actor->id
        );
    }

    public function inviteExternal(User $inviter, string $email, string $displayName, ?string $note = null): ShadowConnection
    {
        $shadow = ShadowConnection::create([
            'id'           => 'sc_' . Str::lower(Str::random(12)),
            'inviter_id'   => $inviter->id,
            'email'        => $email,
            'display_name' => $displayName,
            'note'         => $note,
            'status'       => 'invited',
            'created_at'   => now(),
        ]);

        SendEmailJob::dispatch(
            'emails.network.30-network-invitation',
            ['shadow_id' => $shadow->id, 'inviter_id' => $inviter->id],
            null,
            $email
        );

        return $shadow;
    }

    public function getConnections(string $userId): Collection
    {
        return NetworkConnection::where('user_a_id', $userId)
            ->orWhere('user_b_id', $userId)
            ->get();
    }

    public function getPendingRequests(string $userId): Collection
    {
        return NetworkRequest::where('to_user_id', $userId)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getShadowConnections(string $inviterId): Collection
    {
        return ShadowConnection::where('inviter_id', $inviterId)
            ->whereIn('status', ['invited', 'pending'])
            ->get();
    }

    private function isConnected(string $a, string $b): bool
    {
        return NetworkConnection::where(function ($q) use ($a, $b) {
            $q->where('user_a_id', $a)->where('user_b_id', $b);
        })->orWhere(function ($q) use ($a, $b) {
            $q->where('user_a_id', $b)->where('user_b_id', $a);
        })->exists();
    }

    private function portalFor(string $role): string
    {
        return match ($role) {
            'practitioner'       => 'provider',
            'continuity_steward' => 'continuity_steward',
            'support_steward'    => 'support_steward',
            'business_partner'   => 'business_partner',
            default              => 'provider',
        };
    }
}
