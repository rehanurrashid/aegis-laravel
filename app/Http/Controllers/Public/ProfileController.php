<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\ProfileService;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private ProfileService $profiles) {}

    public function provider(Request $request, string $slug): Response
    {
        $user   = $this->profiles->getPublicProfile($slug);
        abort_if(!$user || $user->role !== 'practitioner' || !$user->practitioner_public, 404);
        $viewer = $request->user();
        return Inertia::render('Public/ProviderProfile', [
            'user'       => $user,
            'viewerRole' => $viewer?->role?->value ?? null,
            'isOwner'    => $viewer && $viewer->id === $user->id,
            'isLoggedIn' => (bool) $viewer,
        ]);
    }

    public function continuityStewarded(Request $request, string $slug): Response
    {
        $user   = $this->profiles->getPublicProfile($slug);
        abort_if(!$user || $user->role !== 'continuity_steward' || !$user->cs_public, 404);
        $viewer = $request->user();
        return Inertia::render('Public/ContinuityStewardProfile', [
            'user'       => $user,
            'viewerRole' => $viewer?->role?->value ?? null,
            'isOwner'    => $viewer && $viewer->id === $user->id,
            'isLoggedIn' => (bool) $viewer,
        ]);
    }

    public function supportSteward(Request $request, string $slug): Response
    {
        $user   = $this->profiles->getPublicProfile($slug);
        abort_if(!$user || $user->role !== 'support_steward', 404);
        $viewer = $request->user();
        return Inertia::render('Public/SupportStewardProfile', [
            'user'       => $user,
            'viewerRole' => $viewer?->role?->value ?? null,
            'isOwner'    => $viewer && $viewer->id === $user->id,
            'isLoggedIn' => (bool) $viewer,
        ]);
    }

    public function business(Request $request, string $slug): Response
    {
        $user   = $this->profiles->getPublicProfile($slug);
        abort_if(!$user || $user->role !== 'business_partner' || !$user->business_partner_public, 404);
        $viewer = $request->user();
        return Inertia::render('Public/BusinessProfile', [
            'user'       => $user,
            'viewerRole' => $viewer?->role?->value ?? null,
            'isOwner'    => $viewer && $viewer->id === $user->id,
            'isLoggedIn' => (bool) $viewer,
        ]);
    }
}
