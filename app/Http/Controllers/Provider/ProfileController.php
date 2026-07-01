<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateAvailabilityRequest;
use App\Http\Requests\Profile\UpdateBasicProfileRequest;
use App\Http\Requests\Profile\UpdateDemographicsRequest;
use App\Http\Requests\Profile\UpdateEducationRequest;
use App\Http\Requests\Profile\UpdateFeesRequest;
use App\Http\Requests\Profile\UpdateLanguagesRequest;
use App\Http\Requests\Profile\UpdateLicensedStatesRequest;
use App\Http\Requests\Profile\UpdateNetworkPreferencesRequest;
use App\Http\Requests\Profile\UpdatePrivacyRequest;
use App\Http\Requests\Profile\UpdateSpecialtiesRequest;
use App\Models\ProviderCredential;
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
        $user->loadMissing('meta');
        $meta = $this->profiles->buildProfileMeta($user, null);

        $credentials = ProviderCredential::where('user_id', $user->id)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Provider/EditProfile', [
            'user'        => $user,
            'credentials' => $credentials,
            'meta' => [
                'specialties'      => $meta['specialties'],
                'services'         => $this->rawMeta($user, 'services', []),
                'approaches'       => $meta['approaches'],
                'fees'             => $meta['fees'],
                'languages'        => $this->rawMeta($user, 'languages', ['English']),
                'website'          => $this->rawMeta($user, 'website', ''),
                'insurance_panels' => $meta['insurance_panels'],
                'licensed_states'  => $this->rawMeta($user, 'licensed_states', []),
                'education'        => $this->rawMeta($user, 'education', []),
                'network_partners' => $this->rawMeta($user, 'network_partners', []),
                'ai_settings'      => $this->rawMeta($user, 'ai_shadow_settings', [
                    'suggestions_mode' => 'on',
                    'max_distance'     => '25',
                    'allow_referral_patterns' => true,
                    'allow_demographics'      => true,
                    'allow_specialties'       => true,
                    'appear_in_suggestions'   => false,
                    'show_in_directory'       => true,
                ]),
                'demographics' => $this->rawMeta($user, 'demographics', []),
                'availability' => [
                    'hours'      => $this->rawMeta($user, 'availability', null),
                    'accepting'  => $this->rawMeta($user, 'network_accepting', true),
                    'telehealth' => $this->rawMeta($user, 'network_telehealth', true),
                ],
                'privacy' => [
                    'practitioner_public'     => $user->practitioner_public,
                    'cs_public'               => $user->cs_public,
                    'business_partner_public' => $user->business_partner_public,
                ],
            ],
        ]);
    }

    public function updateBasic(UpdateBasicProfileRequest $request): RedirectResponse
    {
        $this->profiles->updateBasic($request->user(), $request->validated());
        return back()->with('success', 'Profile updated.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $data = $request->validate(['avatar' => 'required|file|image|max:5120']);
        $this->profiles->updateAvatar($request->user(), $data['avatar']);
        return back()->with('success', 'Photo uploaded.');
    }

    public function removeAvatar(Request $request): RedirectResponse
    {
        $this->profiles->removeAvatar($request->user());
        return back()->with('success', 'Photo removed.');
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

    public function updateApproaches(Request $request): RedirectResponse
    {
        $data = $request->validate(['approaches' => 'required|array']);
        $this->profiles->updateApproaches($request->user(), $data['approaches']);
        return back()->with('success', 'Approaches updated.');
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
        $this->profiles->updateNetwork($request->user(), $request->validated());
        return back()->with('success', 'Network preferences updated.');
    }

    public function updateLanguages(UpdateLanguagesRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->profiles->updateLanguagesAndWebsite($request->user(), $data['languages'], $data['website'] ?? null);
        return back()->with('success', 'Contact details updated.');
    }

    public function updateLicensedStates(UpdateLicensedStatesRequest $request): RedirectResponse
    {
        $this->profiles->updateLicensedStates($request->user(), $request->validated()['states']);
        return back()->with('success', 'Licensed states updated.');
    }

    public function updateEducation(UpdateEducationRequest $request): RedirectResponse
    {
        $this->profiles->updateEducation($request->user(), $request->validated()['education']);
        return back()->with('success', 'Education updated.');
    }

    public function updateNetworkPartners(Request $request): RedirectResponse
    {
        $data = $request->validate(['partners' => 'required|array', 'partners.*' => 'string|max:100']);
        $this->profiles->updateNetworkPartners($request->user(), $data['partners']);
        return back()->with('success', 'Network preferences updated.');
    }

    public function updateAiSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'suggestions_mode'         => 'nullable|string|in:on,paused,off',
            'max_distance'             => 'nullable|string',
            'allow_referral_patterns'  => 'nullable|boolean',
            'allow_demographics'       => 'nullable|boolean',
            'allow_specialties'        => 'nullable|boolean',
            'appear_in_suggestions'    => 'nullable|boolean',
            'show_in_directory'        => 'nullable|boolean',
        ]);
        $this->profiles->updateAiSettings($request->user(), $data);
        return back()->with('success', 'AI & Shadow Network settings updated.');
    }

    public function updateDemographics(UpdateDemographicsRequest $request): RedirectResponse
    {
        $this->profiles->updateDemographics($request->user(), $request->validated());
        return back()->with('success', 'Demographics updated.');
    }

    public function savePrivateNote(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $this->profiles->savePrivateNote($request->user(), trim($data['body']));

        return back()->with('success', 'Note saved.');
    }

    private function rawMeta($user, string $key, mixed $default = null): mixed
    {
        $row = $user->meta->firstWhere('meta_key', $key);
        return $row ? $row->typed_value : $default;
    }
}
