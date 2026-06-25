<?php

declare(strict_types=1);

namespace App\Http\Controllers\BusinessPartner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateBasicProfileRequest;
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
        return Inertia::render('BusinessPartner/EditProfile', [
            'user' => $request->user(),
            'bpType' => $request->user()->bp_type,
        ]);
    }

    public function update(UpdateBasicProfileRequest $request): RedirectResponse
    {
        $this->profiles->updateBasic($request->user(), $request->validated());
        return back()->with('success', 'Profile updated.');
    }
}
