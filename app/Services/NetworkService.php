<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\Network\ConnectionAccepted;
use App\Events\Network\ConnectionRequestSent;

use App\Enums\ActivitySeverity;
use App\Jobs\SendEmailJob;
use App\Models\NetworkConnection;
use App\Models\NetworkRecommendation;
use App\Models\NetworkRequest;
use App\Models\ShadowConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
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
        if (NetworkRequest::where('requester_id', $from->id)
            ->where('recipient_id', $to->id)
            ->where('status', 'pending')->exists()) {
            throw new RuntimeException('A pending request already exists.');
        }

        $req = NetworkRequest::create([
            'id'           => 'nr_' . Str::lower(Str::random(12)),
            'requester_id' => $from->id,
            'recipient_id' => $to->id,
            'message'      => $note,
            'status'       => 'pending',
        ]);

        // Actor self-log (entry_type: log)
        $this->activity->log(
            $from->id,
            $this->portalFor($from->role instanceof \BackedEnum ? $from->role->value : (string) $from->role),
            'account',
            ActivitySeverity::Info,
            'network_request_sent',
            "You sent a connection request to {$to->display_name}",
            $note ? Str::limit($note, 140) : 'Awaiting their response.',
            'network_request',
            $req->id,
            $to->id
        );

        // Recipient notification (entry_type: notification — default)
        $this->activity->log(
            $to->id,
            $this->portalFor($to->role instanceof \BackedEnum ? $to->role->value : (string) $to->role),
            'account',
            ActivitySeverity::Info,
            'network_request_received',
            "{$from->display_name} sent you a network connection request",
            $note ? Str::limit($note, 140) : 'Tap to review and accept or decline.',
            'network_request',
            $req->id,
            $from->id
        );

        event(new ConnectionRequestSent($req, $from, $to));

        return $req;
    }

    public function acceptRequest(NetworkRequest $req, User $accepter): NetworkConnection
    {
        if ($req->recipient_id !== $accepter->id) {
            throw new RuntimeException('Only the recipient can accept.');
        }
        if ($req->status !== \App\Enums\RequestStatus::Pending) {
            throw new RuntimeException('Request is not pending.');
        }

        return DB::transaction(function () use ($req, $accepter) {
            $req->update(['status' => 'accepted', 'responded_at' => now()]);

            $conn = NetworkConnection::create([
                'id'                => 'nc_' . Str::lower(Str::random(12)),
                'user_id'           => $req->requester_id,
                'connected_user_id' => $req->recipient_id,
                'connection_type'   => 'practitioner',
                'status'            => 'active',
                'connected_at'      => now(),
            ]);

            $from = User::find($req->requester_id);

            // Accepter self-log
            $this->activity->log(
                $accepter->id,
                $this->portalFor($accepter->role instanceof \BackedEnum ? $accepter->role->value : (string) $accepter->role),
                'account',
                ActivitySeverity::Info,
                'network_request_accepted_self',
                "You accepted a connection request from {$from->display_name}",
                'You are now connected.',
                'network_connection',
                $conn->id,
                $from->id
            );

            // Requester notification
            $this->activity->log(
                $from->id,
                $this->portalFor($from->role instanceof \BackedEnum ? $from->role->value : (string) $from->role),
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
        if ($req->recipient_id !== $decliner->id) {
            throw new RuntimeException('Only the recipient can decline.');
        }
        $req->update([
            'status'       => 'declined',
            'responded_at' => now(),
        ]);

        $requester = User::find($req->requester_id);
        // Decliner self-log (silent to requester by design)
        $this->activity->log(
            $decliner->id,
            $this->portalFor($decliner->role instanceof \BackedEnum ? $decliner->role->value : (string) $decliner->role),
            'account',
            ActivitySeverity::Info,
            'network_request_declined_self',
            "You declined a connection request" . ($requester ? " from {$requester->display_name}" : ''),
            $reason ?? 'Request declined.',
            'network_request',
            $req->id,
            $req->requester_id
        );

        return $req->fresh();
    }

    public function disconnect(NetworkConnection $conn, User $actor): void
    {
        $conn->delete();

        $otherId = $conn->user_id === $actor->id ? $conn->connected_user_id : $conn->user_id;
        $other = User::find($otherId);

        if ($other) {
            $this->activity->log(
                $other->id,
                $this->portalFor($other->role instanceof \BackedEnum ? $other->role->value : (string) $other->role),
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
    }

    public function inviteExternal(User $inviter, string $email, string $displayName, ?string $note = null): ShadowConnection
    {
        $shadow = ShadowConnection::create([
            'id'          => 'sc_' . Str::lower(Str::random(12)),
            'user_id'     => $inviter->id,
            'shadow_name' => $displayName,
            'source'      => 'email_invite',
            'created_at'  => now(),
        ]);

        SendEmailJob::dispatch(
            'emails.network.30-network-invitation',
            ['shadow_id' => $shadow->id, 'inviter_id' => $inviter->id],
            null,
            $email
        );

        return $shadow;
    }

    /**
     * Add a provider to the current user's referral (shadow) list manually.
     * Unlike inviteExternal(), no email is sent — this covers the case where
     * a practitioner wants to keep a personal referral note about someone
     * who is not (yet) an Aegis user. Source is 'manual_add' so downstream
     * reporting can distinguish these from auto-suggested shadows.
     */
    public function addShadowManual(User $inviter, string $displayName, ?string $note = null): ShadowConnection
    {
        $shadow = ShadowConnection::create([
            'id'          => 'sc_' . Str::lower(Str::random(12)),
            'user_id'     => $inviter->id,
            'shadow_name' => $displayName,
            'source'      => 'manual_add',
            'created_at'  => now(),
        ]);

        $this->activity->log(
            $inviter->id,
            'provider',
            'account',
            ActivitySeverity::Info,
            'network_shadow_added',
            "Added {$displayName} to your referral list",
            $note ? Str::limit($note, 140) : 'A manual referral-list entry was created.',
            'shadow_connection',
            $shadow->id,
            null
        );

        return $shadow;
    }

    public function getConnections(string $userId): Collection
    {
        return NetworkConnection::where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhere('connected_user_id', $userId);
        })
            ->where('status', 'active')
            ->with(['target', 'owner'])
            ->get();
    }

    public function getPendingRequests(string $userId): Collection
    {
        return NetworkRequest::with('requester')->where('recipient_id', $userId)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getShadowConnections(string $inviterId): Collection
    {
        return ShadowConnection::with('shadowUser')->where('user_id', $inviterId)
            ->get();
    }

    /**
     * Recommended specialty categories ("Recommended Network Partners" row).
     * Returns user-specific rows if seeded, otherwise falls back to globals
     * (rows with user_id = NULL). Frontend consumes these as `rnp-card`s.
     */
    public function getRecommendedPartnerCategories(string $userId): SupportCollection
    {
        $rows = NetworkRecommendation::where('kind', 'partner_category')
            ->where('user_id', $userId)
            ->orderBy('sort_order')
            ->get();

        if ($rows->isEmpty()) {
            $rows = NetworkRecommendation::where('kind', 'partner_category')
                ->whereNull('user_id')
                ->orderBy('sort_order')
                ->get();
        }

        return $rows->map(function (NetworkRecommendation $r) {
            $label   = $r->label ?? '';
            $keyword = trim(explode('/', $label)[0]); // strip " / LCSW" suffix

            if ($keyword && $r->priority !== 'biz') {
                // Count public practitioners matching this category label.
                // Uses the same filters as the searchProviders query so the
                // count matches what the filter will actually surface.
                $realCount = \App\Models\User::where('practitioner_public', 1)
                    ->where('role', 'practitioner')
                    ->where(function ($q) use ($keyword) {
                        $q->where('title', 'like', "%{$keyword}%")
                          ->orWhere('specialty', 'like', "%{$keyword}%");
                    })
                    ->count();
            } elseif ($r->priority === 'biz') {
                $realCount = \App\Models\User::where('business_partner_public', 1)
                    ->where('role', 'business_partner')
                    ->where(function ($q) use ($keyword) {
                        $q->where('title', 'like', "%{$keyword}%")
                          ->orWhere('specialty', 'like', "%{$keyword}%");
                    })
                    ->count();
            } else {
                $realCount = 0;
            }

            return [
                'id'          => $r->id,
                'label'       => $label,
                'description' => $r->description ?? '',
                'icon'        => $r->icon ?? 'users',
                'count'       => $realCount,
                'priority'    => $r->priority ?? 'medium',
                'tier'        => 'is-' . ($r->priority ?? 'medium'),
            ];
        });
    }

    /**
     * Recommended shadow providers ("Recommended Shadow Providers" row).
     * Joins the referenced user for display fields. Rows whose provider
     * user was deleted are skipped defensively.
     */
    public function getRecommendedShadowProviders(string $userId): SupportCollection
    {
        $rows = NetworkRecommendation::with('providerUser')
            ->where('kind', 'shadow_provider')
            ->where('user_id', $userId)
            ->orderBy('sort_order')
            ->get();

        if ($rows->isEmpty()) {
            $rows = NetworkRecommendation::with('providerUser')
                ->where('kind', 'shadow_provider')
                ->whereNull('user_id')
                ->orderBy('sort_order')
                ->get();
        }

        return $rows
            ->filter(fn (NetworkRecommendation $r) => $r->providerUser !== null)
            ->filter(function (NetworkRecommendation $r) {
                $role = $r->providerUser->role instanceof \BackedEnum
                    ? $r->providerUser->role->value
                    : (string) $r->providerUser->role;
                return $role === 'practitioner';
            })
            ->map(function (NetworkRecommendation $r) {
                $u = $r->providerUser;
                $tags = array_values(array_filter(array_map(
                    'trim',
                    explode(',', (string) ($u->specialty ?? ''))
                )));

                return [
                    'id'         => $u->id,
                    'name'       => $u->display_name . ($u->credentials ? ', ' . $u->credentials : ''),
                    'slug'       => $u->slug ?? '',
                    'initials'   => $u->avatar_initials ?? strtoupper(substr($u->display_name, 0, 2)),
                    'role'       => $u->title ?? '',
                    'location'   => $u->location ?? '',
                    'tags'       => array_slice($tags, 0, 3),
                    'match'      => (int) ($r->match_score ?? 0),
                    'rating'     => 0,   // Peer-rating aggregation is a Phase-4 concern
                    'telehealth' => false,
                    'connected'  => false,
                ];
            })
            ->values();
    }

    private function isConnected(string $a, string $b): bool
    {
        return NetworkConnection::where(function ($q) use ($a, $b) {
            $q->where('user_id', $a)->where('connected_user_id', $b);
        })->orWhere(function ($q) use ($a, $b) {
            $q->where('user_id', $b)->where('connected_user_id', $a);
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
