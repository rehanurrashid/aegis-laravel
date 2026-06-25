<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateAvailabilityRequest;
use App\Http\Requests\Profile\UpdateBasicProfileRequest;
use App\Http\Requests\Profile\UpdateCredentialsRequest;
use App\Http\Requests\Profile\UpdateFeesRequest;
use App\Http\Requests\Profile\UpdateNetworkPreferencesRequest;
use App\Http\Requests\Profile\UpdatePrivacyRequest;
use App\Http\Requests\Profile\UpdateSpecialtiesRequest;
use App\Services\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(private ProfileService $profiles) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        return Inertia::render('Provider/EditProfile', [
            'user' => $user,
            'meta' => [
                'specialties' => $user->specialty ? json_decode($user->specialty, true) : [],
                'services'    => $this->jsonField($user->profile_meta, 'services', []),
                'approaches'  => $this->jsonField($user->profile_meta, 'approaches', []),
                'credentials' => $this->jsonField($user->profile_meta, 'credentials', []),
                'fees'        => $this->jsonField($user->profile_meta, 'fees', []),
                'availability'=> [
                    'hours'      => $user->network_hours ? json_decode($user->network_hours, true) : null,
                    'accepting'  => $user->network_accepting,
                    'telehealth' => $user->network_telehealth,
                ],
                'privacy' => [
                    'practitioner_public'    => $user->practitioner_public,
                    'cs_public'              => $user->cs_public,
                    'business_partner_public'=> $user->business_partner_public,
                ],
            ],
        ]);
    }

    public function updateBasic(UpdateBasicProfileRequest $request): RedirectResponse
    {
        $this->profiles->updateBasic($request->user(), $request->validated());
        return back()->with('success', 'Profile updated.');
    }

    public function updateCredentials(UpdateCredentialsRequest $request): RedirectResponse
    {
        $this->profiles->updateCredentials($request->user(), $request->validated());
        return back()->with('success', 'Credentials updated.');
    }

    public function updateSpecialties(UpdateSpecialtiesRequest $request): RedirectResponse
    {
        $this->profiles->updateSpecialties($request->user(), $request->validated()['specialties']);
        return back()->with('success', 'Specialties updated.');
    }

    public function updateServices(Request $request): RedirectResponse
    {
        $data = $request->validate(['services' => 'required|array']);
        $this->profiles->updateServices($request->user(), $data['services']);
        return back()->with('success', 'Services updated.');
    }

    public function updateFees(UpdateFeesRequest $request): RedirectResponse
    {
        $this->profiles->updateFees($request->user(), $request->validated());
        return back()->with('success', 'Fees updated.');
    }

    public function updateAvailability(UpdateAvailabilityRequest $request): RedirectResponse
    {
        $this->profiles->updateAvailability($request->user(), $request->validated());
        return back()->with('success', 'Availability updated.');
    }

    public function updatePrivacy(UpdatePrivacyRequest $request): RedirectResponse
    {
        $this->profiles->updatePrivacy($request->user(), $request->validated());
        return back()->with('success', 'Privacy settings updated.');
    }

    public function updateNetwork(UpdateNetworkPreferencesRequest $request): RedirectResponse
    {
        $this->profiles->updateNetworkPreferences($request->user(), $request->validated());
        return back()->with('success', 'Network preferences updated.');
    }

    private function jsonField(?string $blob, string $key, mixed $default = null): mixed
    {
        if (!$blob) return $default;
        $data = json_decode($blob, true);
        return $data[$key] ?? $default;
    }
}
