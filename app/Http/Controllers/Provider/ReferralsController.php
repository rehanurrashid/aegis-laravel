<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\NetworkConnection;
use App\Models\Referral;
use App\Models\VaultItem;
use App\Services\NetworkService;
use App\Services\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReferralsController extends Controller
{
    public function __construct(
        private ReferralService $referralService,
        private NetworkService  $network,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        // Access-tier guard — redirect to Settings upgrade flow
        if ($user->tier?->value === 'access') {
            return redirect()->route('provider.settings.index', ['section' => 'billing', 'upgrade' => '1']);
        }

        // ── All referrals for this user ──────────────────────────────
        $allForUser = Referral::with([
                'sender:id,display_name,credentials,slug,avatar_path,specialty,location,avatar_initials',
                'recipient:id,display_name,credentials,slug,avatar_path,specialty,location,avatar_initials',
                'meta',
            ])
            ->where(function ($q) use ($user) {
                $q->where('sender_id', $user->id)->orWhere('recipient_id', $user->id);
            })
            ->orderByDesc('created_at')
            ->get();

        // IDs of users this provider is actively connected to — used in $serialize for is_connected flag
        $networkIds = \App\Models\NetworkConnection::where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('connected_user_id')
            ->merge(
                \App\Models\NetworkConnection::where('connected_user_id', $user->id)
                    ->where('status', 'active')
                    ->pluck('user_id')
            )
            ->unique()
            ->values()
            ->all();

        $serialize = function (Referral $r) use ($user, $networkIds) {
            $status = $r->status instanceof \BackedEnum ? $r->status->value : (string) $r->status;
            $isSender = $r->sender_id === $user->id;
            return [
                'id'                      => $r->id,
                'status'                  => $status,
                'direction'               => $isSender ? 'sent' : 'received',
                'subject'                 => $r->subject,
                'responded_at'            => $r->responded_at?->toISOString(),
                'closed_at'               => $r->closed_at?->toISOString(),
                'created_at'              => $r->created_at?->toISOString(),
                'counterpart_user_id'     => $isSender
                    ? $r->recipient_id
                    : $r->sender_id,
                'counterpart_name'        => $isSender
                    ? ($r->recipient?->display_name ?? '—')
                    : ($r->sender?->display_name ?? '—'),
                'counterpart_credentials' => $isSender
                    ? $r->recipient?->credentials
                    : $r->sender?->credentials,
                'counterpart_slug'        => $isSender
                    ? $r->recipient?->slug
                    : $r->sender?->slug,
                'counterpart_avatar'      => $isSender
                    ? $r->recipient?->avatar_path
                    : $r->sender?->avatar_path,
                'counterpart_initials'    => $isSender
                    ? ($r->recipient?->avatar_initials ?? strtoupper(substr($r->recipient?->display_name ?? '', 0, 2)))
                    : ($r->sender?->avatar_initials    ?? strtoupper(substr($r->sender?->display_name    ?? '', 0, 2))),
                'counterpart_specialty'   => $isSender
                    ? $r->recipient?->specialty
                    : $r->sender?->specialty,
                'counterpart_location'    => $isSender
                    ? $r->recipient?->location
                    : $r->sender?->location,
                'counterpart_is_connected' => in_array(
                    $isSender ? $r->recipient_id : $r->sender_id,
                    $networkIds, true
                ),
                'client_initials'         => $r->meta->firstWhere('meta_key', 'client_initials')?->meta_value,
                'client_age_band'         => $r->meta->firstWhere('meta_key', 'client_age_range')?->meta_value
                                         ?? $r->meta->firstWhere('meta_key', 'client_age_band')?->meta_value,
                'reason'                  => $r->meta->firstWhere('meta_key', 'presenting_issue')?->meta_value
                                         ?? $r->meta->firstWhere('meta_key', 'reason')?->meta_value
                                         ?? $r->subject,
                'urgency'                 => $r->meta->firstWhere('meta_key', 'urgency')?->meta_value ?? 'routine',
                'notes'                   => $r->meta->firstWhere('meta_key', 'notes')?->meta_value,
                'decline_reason'          => $r->meta->firstWhere('meta_key', 'decline_reason')?->meta_value,
            ];
        };

        $pending   = $allForUser->filter(fn($r) => $r->recipient_id === $user->id && in_array(
                $r->status instanceof \BackedEnum ? $r->status->value : (string)$r->status, ['sent']
            ))->values()->map($serialize);

        $sentActive = $allForUser->filter(fn($r) => $r->sender_id === $user->id && !in_array(
                $r->status instanceof \BackedEnum ? $r->status->value : (string)$r->status,
                ['closed', 'cancelled']
            ))->values()->map($serialize);

        $completedThisMonth = $allForUser->filter(function ($r) {
            $status = $r->status instanceof \BackedEnum ? $r->status->value : (string)$r->status;
            return $status === 'closed' && $r->closed_at && $r->closed_at->isCurrentMonth();
        })->values()->map($serialize);

        $all = $allForUser->map($serialize)->values();

        $archived = $allForUser->filter(function ($r) {
            $status = $r->status instanceof \BackedEnum ? $r->status->value : (string)$r->status;
            return in_array($status, ['closed', 'cancelled', 'declined']);
        })->values()->map($serialize);

        $acceptRate = $allForUser->count()
            ? (int) round(
                $allForUser->filter(fn($r) => in_array(
                    $r->status instanceof \BackedEnum ? $r->status->value : (string)$r->status,
                    ['accepted', 'closed']
                ))->count() / $allForUser->count() * 100
            )
            : 0;

        // ── Roster items for ReferralModal (vault zone=roster) ───────
        $roster = VaultItem::where('practitioner_id', $user->id)
            ->where('zone', 'roster')
            ->whereNotNull('client_name')
            ->orderBy('client_priority')
            ->get()
            ->map(fn($v) => [
                'id'          => $v->id,
                'title'       => $v->title,
                'client_name' => $v->client_name,
                'category'    => $v->category,
                'sub_label'   => $v->sub_label,
                'status'      => $v->status instanceof \BackedEnum ? $v->status->value : (string)$v->status,
            ]);

        // ── Clinical network for ReferralModal — bidirectional, same as NetworkController ──
        $allConnections = $this->network->getConnections($user->id);

        $connType = fn($c) => $c->connection_type instanceof \BackedEnum
            ? $c->connection_type->value : (string) $c->connection_type;

        $network = $allConnections
            ->filter(fn($c) => in_array($connType($c), ['practitioner', 'clinical']))
            ->values()
            ->map(function ($nc) use ($user) {
                $u = ($nc->user_id === $user->id) ? $nc->target : $nc->owner;
                if (!$u) return null;
                return [
                    'id'           => $u->id,
                    'display_name' => $u->display_name,
                    'credentials'  => $u->credentials,
                    'slug'         => $u->slug,
                    'specialty'    => $u->specialty,
                    'location'     => $u->location,
                    'initials'     => $u->avatar_initials ?? strtoupper(substr($u->display_name ?? '', 0, 2)),
                    'avatar_url'   => $u->avatar_path,
                    'accepting'    => (bool) ($u->practitioner_public ?? true),
                    'is_connected' => true,
                ];
            })->filter()->values();

        return Inertia::render('provider/Referrals', [
            'incomingReferrals'  => $pending,
            'sentReferrals'      => $sentActive,
            'completedReferrals' => $completedThisMonth,
            'allReferrals'       => $all,
            'archivedReferrals'  => $archived,
            'refStats'           => [
                'pending'         => $pending->count(),
                'sent_active'     => $sentActive->count(),
                'completed_month' => $completedThisMonth->count(),
                'accept_rate'     => $acceptRate,
            ],
            'archivedCounts'     => [
                'expired'   => $archived->filter(fn($r) => !$r['responded_at'] && $r['status'] !== 'declined')->count(),
                'completed' => $archived->filter(fn($r) => $r['status'] === 'closed')->count(),
                'declined'  => $archived->filter(fn($r) => $r['status'] === 'declined')->count(),
            ],
            'roster'  => $roster,
            'network' => $network,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider_id'     => 'required|string|exists:users,id',
            'client_name'     => 'required|string|max:191',
            'diagnosis'       => 'nullable|string|max:500',
            'urgency'         => 'nullable|string|in:routine,soon,urgent',
            'note'            => 'nullable|string|max:2000',
            'service_format'  => 'nullable|string|in:in_person,online,any',
            'accepts_cash'    => 'nullable|boolean',
            'client_consents' => 'nullable|boolean',
            'roster_item_id'  => 'nullable|string',
        ]);

        $recipient = \App\Models\User::findOrFail($data['provider_id']);

        $this->referralService->send($request->user(), $recipient, [
            'client_initials' => $data['client_name'],
            'reason'          => $data['diagnosis'] ?? null,
            'urgency'         => $data['urgency'] ?? 'routine',
            'notes'           => $data['note'] ?? null,
            'subject'         => $data['diagnosis'] ?? $data['client_name'],
        ]);

        return back()->with('success', 'Referral sent.');
    }

    public function accept(Request $request, Referral $referral): RedirectResponse
    {
        $this->authorize('respond', $referral);
        $this->referralService->accept($referral);
        return back()->with('success', 'Referral accepted — referring provider notified.');
    }

    public function decline(Request $request, Referral $referral): RedirectResponse
    {
        $this->authorize('respond', $referral);
        $this->referralService->decline($referral, $request->input('reason'));
        return back()->with('success', 'Referral declined — referring provider notified.');
    }

    public function cancel(Request $request, Referral $referral): RedirectResponse
    {
        abort_unless($referral->sender_id === $request->user()->id, 403);
        $this->referralService->cancel($referral, $request->user());
        return back()->with('success', 'Referral cancelled — moved to archive.');
    }

    public function complete(Request $request, Referral $referral): RedirectResponse
    {
        abort_unless(
            $referral->sender_id === $request->user()->id || $referral->recipient_id === $request->user()->id,
            403
        );
        $this->referralService->close($referral, $request->user());
        return back()->with('success', 'Referral marked complete.');
    }
}
