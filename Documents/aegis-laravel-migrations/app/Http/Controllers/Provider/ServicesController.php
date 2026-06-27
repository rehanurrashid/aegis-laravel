<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceRequest;
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
        $user = $request->user();
        return Inertia::render('Provider/Services', [
            'myServices'       => $this->services->getForPractitioner($user->id),
            'incomingRequests' => ServiceRequest::where('practitioner_id', $user->id)->where('status', 'pending')->get(),
            'findProviders'    => $this->services->findProviders(['limit' => 20]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'        => 'required|string|max:200',
            'description'  => 'nullable|string|max:5000',
            'category'     => 'nullable|string|max:100',
            'price_cents'  => 'required|integer|min:0',
            'duration_min' => 'nullable|integer|min:15|max:480',
        ]);
        $this->services->create($request->user(), $data);
        return back()->with('success', 'Service created.');
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $this->authorize('manage', $service);
        $data = $request->validate([
            'title'        => 'nullable|string|max:200',
            'description'  => 'nullable|string|max:5000',
            'price_cents'  => 'nullable|integer|min:0',
            'duration_min' => 'nullable|integer|min:15|max:480',
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

    public function acceptRequest(Service $service, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('manage', $service);
        $this->services->acceptRequest($serviceRequest);
        return back()->with('success', 'Request accepted.');
    }

    public function declineRequest(Request $request, Service $service, ServiceRequest $serviceRequest): RedirectResponse
    {
        $this->authorize('manage', $service);
        $this->services->declineRequest($serviceRequest, $request->input('reason'));
        return back()->with('success', 'Request declined.');
    }
}
