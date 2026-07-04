<?php

declare(strict_types=1);

namespace App\Http\Controllers\BusinessPartner;

use App\Http\Controllers\Controller;
use App\Models\BpContract;
use App\Models\BpEngagementRequest;
use App\Models\BpInvoice;
use App\Models\BpProposal;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private ActivityService $activity) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $contracts = BpContract::where('bp_id', $user->id);
        $invoices = BpInvoice::where('bp_id', $user->id);

        $ytdCents = (clone $invoices)->where('status', 'paid')
            ->whereYear('paid_at', now()->year)->sum('total_cents');

        return Inertia::render('BusinessPartner/Dashboard', [
            'bpType' => $user->bp_type,
            'stats'  => [
                'active_contracts'   => (clone $contracts)->where('status', 'active')->count(),
                'pending_proposals'  => BpProposal::where('bp_id', $user->id)->where('status', 'submitted')->count(),
                'ytd_cents'          => (int) $ytdCents,
                'open_invoices'      => (clone $invoices)->whereIn('status', ['sent', 'partial'])->count(),
                'pending_engagement_requests' => BpEngagementRequest::where('bp_id', $user->id)->where('status', 'pending')->count(),
            ],
            'activeContracts'  => (clone $contracts)->where('status', 'active')->get(),
            'pendingProposals' => BpProposal::where('bp_id', $user->id)
                                    ->where('status', 'submitted')->orderByDesc('submitted_at')->limit(5)->get(),
            'pendingEngagementRequests' => BpEngagementRequest::with('practitioner:id,display_name,avatar_initials,slug')
                                    ->where('bp_id', $user->id)
                                    ->where('status', 'pending')
                                    ->orderByDesc('created_at')
                                    ->limit(5)
                                    ->get()
                                    ->map(fn ($r) => [
                                        'id'    => $r->id,
                                        'type'  => $r->type,
                                        'label' => match ($r->type) {
                                            'hire'         => 'Engagement — ' . ($r->engagement_type ?? 'Custom'),
                                            'quote'        => 'Quote — ' . ($r->service ?? 'General'),
                                            'consultation' => 'Consultation — ' . ($r->start_date?->format('M j') ?? ''),
                                            default        => ucfirst($r->type),
                                        },
                                        'from'       => $r->practitioner?->display_name,
                                        'created_at' => $r->created_at->format('M j, g:i A'),
                                    ])->values()->toArray(),
            'recentActivity'   => $this->activity->getForUser($user->id, [], 10),
        ]);
    }
}
