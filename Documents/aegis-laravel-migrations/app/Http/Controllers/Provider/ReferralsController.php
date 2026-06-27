<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ReferralsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $sent     = Referral::where('practitioner_id', $user->id)->get();
        $received = Referral::where('counterpart_id', $user->id)->orWhere('counterpart_slug', $user->slug)->get();

        return Inertia::render('Provider/Referrals', [
            'sent'     => $sent,
            'received' => $received,
            'stats'    => [
                'pending'   => $sent->where('status', 'pending')->count() + $received->where('status', 'pending')->count(),
                'accepted'  => $sent->where('status', 'accepted')->count() + $received->where('status', 'accepted')->count(),
                'completed' => $sent->where('status', 'completed')->count() + $received->where('status', 'completed')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'counterpart_slug' => 'required|string',
            'reason'           => 'required|string|min:10|max:1000',
            'urgency'          => 'nullable|string|in:routine,soon,urgent',
        ]);
        Referral::create([
            'id'              => 'rf_' . Str::lower(Str::random(12)),
            'practitioner_id' => $request->user()->id,
            'counterpart_slug'=> $data['counterpart_slug'],
            'reason'          => $data['reason'],
            'urgency'         => $data['urgency'] ?? 'routine',
            'status'          => 'pending',
            'created_at'      => now(),
        ]);
        return back()->with('success', 'Referral sent.');
    }

    public function accept(Request $request, Referral $referral): RedirectResponse
    {
        $this->authorize('respond', $referral);
        $referral->update(['status' => 'accepted', 'responded_at' => now()]);
        return back()->with('success', 'Referral accepted.');
    }

    public function decline(Request $request, Referral $referral): RedirectResponse
    {
        $this->authorize('respond', $referral);
        $referral->update(['status' => 'declined', 'responded_at' => now(), 'decline_reason' => $request->input('reason')]);
        return back()->with('success', 'Referral declined.');
    }
}
