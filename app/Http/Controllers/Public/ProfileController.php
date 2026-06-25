<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\ProfileService;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(private ProfileService $profiles) {}

    public function provider(string $slug): Response
    {
        $user = $this->profiles->getPublicProfile($slug);
        abort_if(!$user || $user->role !== 'practitioner' || !$user->practitioner_public, 404);
        return Inertia::render('Public/ProviderProfile', ['user' => $user]);
    }

    public function continuityStewarded(string $slug): Response
    {
        $user = $this->profiles->getPublicProfile($slug);
        abort_if(!$user || $user->role !== 'continuity_steward' || !$user->cs_public, 404);
        return Inertia::render('Public/ContinuityStewardProfile', ['user' => $user]);
    }

    public function supportSteward(string $slug): Response
    {
        $user = $this->profiles->getPublicProfile($slug);
        abort_if(!$user || $user->role !== 'support_steward', 404);
        return Inertia::render('Public/SupportStewardProfile', ['user' => $user]);
    }

    public function business(string $slug): Response
    {
        $user = $this->profiles->getPublicProfile($slug);
        abort_if(!$user || $user->role !== 'business_partner' || !$user->business_partner_public, 404);
        return Inertia::render('Public/BusinessProfile', ['user' => $user]);
    }
}
