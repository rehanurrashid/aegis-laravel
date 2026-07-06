<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceSession;
use App\Services\ServiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServicesController extends Controller
{
    public function __construct(private ServiceService $services) {}

    public function index(Request $request): Response
    {
        $user    = $request->user();
        $filters = $request->only(['q', 'category', 'status']);

        return Inertia::render('Provider/Services', [
            'listings'         => $this->services->getForPractitioner($user->id, $filters)
                                    ->map(fn($s) => $this->services->shapeForListing($s))
                                    ->values(),
            'serviceRequests'  => $this->services->getRequestsForPractitioner($user->id)
                                    ->map(fn($r) => $this->services->shapeRequest($r))
                                    ->values(),
            'bookings'         => $this->services->getSessionsForPractitioner($user->id)
                                    ->map(fn($s) => $this->services->shapeSession($s))
                                    ->values(),
            'stats'            => $this->services->statsForPractitioner($user),
            'profileCompletion'=> (int) ($user->profile_completion ?? 0),
            'servicesMode'     => (bool) $user->services_mode,
            'heroRating'       => '4.8 / 5.0',
            'filters'          => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'              => 'required|string|max:200',
            'description'        => 'nullable|string|max:5000',
            'category'           => 'nullable|string|max:100',
            'price_cents'        => 'nullable|integer|min:0',
            'price_type'         => 'nullable|string|in:fixed,hourly,session,inquiry',
            'duration_min'       => 'nullable|integer|min:5|max:480',
            'format'             => 'nullable|string|in:telehealth,in_person,both',
            'availability'       => 'nullable|string|in:open,limited',
            'availability_label' => 'nullable|string|max:60',
            'is_public'          => 'nullable|boolean',
            'status'             => 'nullable|string|in:active,draft',
        ]);
        $this->services->create($request->user(), $data);
        return back()->with('success', 'Service created.');
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $this->authorize('manage', $service);
        $data = $request->validate([
            'title'              => 'nullable|string|max:200',
            'description'        => 'nullable|string|max:5000',
            'category'           => 'nullable|string|max:100',
            'price_cents'        => 'nullable|integer|min:0',
            'price_type'         => 'nullable|string|in:fixed,hourly,session,inquiry',
            'duration_min'       => 'nullable|integer|min:5|max:480',
            'format'             => 'nullable|string|in:telehealth,in_person,both',
            'availability'       => 'nullable|string|in:open,limited',
            'availability_label' => 'nullable|string|max:60',
            'status'             => 'nullable|string|in:active,inactive,draft,paused,archived',
            'is_public'          => 'nullable|boolean',
        ]);
        $this->services->update($service, $data);
        return back()->with('success', 'Service updated.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->authorize('manage', $service);
        $this->services->archive($service);
        return back()->with('success', 'Service archived.');
    }

    public function acceptRequest(Request $request, Service $service, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('manage', $service);
        $data = $request->validate([
            'session_date' => 'nullable|date',
            'session_time' => 'nullable|string|max:10',
            'timezone'     => 'nullable|string|max:64',
            'format'       => 'nullable|string|max:60',
            'note'         => 'nullable|string|max:1000',
            'recurring'    => 'nullable|boolean',
        ]);
        $this->services->acceptRequest($serviceRequest, $data);
        return back()->with('success', 'Request accepted.');
    }

    public function declineRequest(Request $request, Service $service, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('manage', $service);
        $this->services->declineRequest($serviceRequest, $request->input('reason'));
        return back()->with('success', 'Request declined.');
    }

    public function cancelSession(Request $request, ServiceSession $session): RedirectResponse
    {
        $this->authorize('manage', $session->service);
        $data = $request->validate([
            'reason'           => 'required|string|max:255',
            'note'             => 'nullable|string|max:1000',
            'offer_reschedule' => 'nullable|boolean',
        ]);
        $this->services->cancelSession($session, $data);
        return back()->with('success', 'Session cancelled.');
    }

    public function saveSessionNotes(Request $request, ServiceSession $session): RedirectResponse
    {
        $this->authorize('manage', $session->service);
        $data = $request->validate([
            'summary'               => 'nullable|string|max:5000',
            'action_items'          => 'nullable|string|max:2000',
            'share_with_supervisee' => 'nullable|boolean',
        ]);
        $this->services->saveSessionNotes($session, $data);
        return back()->with('success', 'Notes saved.');
    }
}
