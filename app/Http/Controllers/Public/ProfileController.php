<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Services\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(private ProfileService $profiles) {}

    public function provider(Request $request, string $slug): Response
    {
        $user   = $this->profiles->getPublicProfile($slug);
        $viewer = Auth::user();

        abort_if(
            ! $user
            || $user->role !== UserRole::Practitioner
            || ! $user->practitioner_public,
            404
        );

        $isOwner    = $viewer && $viewer->id === $user->id;
        $isLoggedIn = (bool) $viewer;
        $profileMeta = $this->profiles->buildProfileMeta($user, $viewer);

        $services = \App\Models\Service::where('practitioner_id', $user->id)
            ->active()
            ->public()
            ->orderBy('title')
            ->get();

        // Strip sensitive contact info for anonymous viewers
        if (! $isLoggedIn) {
            $user->makeHidden(['email', 'phone']);
        }

        return Inertia::render('public/ProviderProfile', [
            'user'        => $user,
            'profileMeta' => $profileMeta,
            'services'    => $services,
            'viewerRole'  => $viewer?->role?->value ?? null,
            'isOwner'     => $isOwner,
            'isLoggedIn'  => $isLoggedIn,
        ]);
    }

    public function continuityStewarded(Request $request, string $slug): Response
    {
        $user   = $this->profiles->getPublicProfile($slug);
        $viewer = Auth::user();

        abort_if(
            ! $user
            || $user->role !== UserRole::ContinuitySteward
            || ! $user->cs_public,
            404
        );

        $isOwner    = $viewer && $viewer->id === $user->id;
        $isLoggedIn = (bool) $viewer;
        $profileMeta = $this->profiles->buildProfileMeta($user, $viewer);

        if (! $isLoggedIn) {
            $user->makeHidden(['email', 'phone']);
        }

        return Inertia::render('public/ContinuityStewardProfile', [
            'user'        => $user,
            'profileMeta' => $profileMeta,
            'viewerRole'  => $viewer?->role?->value ?? null,
            'isOwner'     => $isOwner,
            'isLoggedIn'  => $isLoggedIn,
        ]);
    }

    public function supportSteward(Request $request, string $slug): Response
    {
        $user   = $this->profiles->getPublicProfile($slug);
        $viewer = Auth::user();

        abort_if(! $user || $user->role !== UserRole::SupportSteward, 404);

        // SS profiles are relationship-gated: only owner or linked Provider
        $invitedById = $user->invited_by_id ?? null;
        $viewerIsLinkedProvider = $viewer && $invitedById && $viewer->id === $invitedById;
        $isOwner = $viewer && $viewer->id === $user->id;

        abort_if(! $isOwner && ! $viewerIsLinkedProvider, 404);

        $isLoggedIn = (bool) $viewer;
        $profileMeta = $this->profiles->buildProfileMeta($user, $viewer);

        return Inertia::render('public/SupportStewardProfile', [
            'user'        => $user,
            'profileMeta' => $profileMeta,
            'viewerRole'  => $viewer?->role?->value ?? null,
            'isOwner'     => $isOwner,
            'isLoggedIn'  => $isLoggedIn,
        ]);
    }

    public function business(Request $request, string $slug): Response
    {
        $user   = $this->profiles->getPublicProfile($slug);
        $viewer = Auth::user();

        abort_if(
            ! $user
            || $user->role !== UserRole::BusinessPartner
            || ! $user->business_partner_public,
            404
        );

        $isOwner    = $viewer && $viewer->id === $user->id;
        $isLoggedIn = (bool) $viewer;
        $profileMeta = $this->profiles->buildProfileMeta($user, $viewer);

        if (! $isLoggedIn) {
            $user->makeHidden(['email', 'phone']);
        }

        return Inertia::render('public/BusinessProfile', [
            'user'        => $user,
            'profileMeta' => $profileMeta,
            'viewerRole'  => $viewer?->role?->value ?? null,
            'isOwner'     => $isOwner,
            'isLoggedIn'  => $isLoggedIn,
        ]);
    }
}
