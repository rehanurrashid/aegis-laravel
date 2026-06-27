<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePackagePriceRequest;
use App\Services\AdminPackageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PackagesController extends Controller
{
    public function __construct(private AdminPackageService $packages) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Packages', [
            'packages' => $this->packages->getAll(),
        ]);
    }

    public function updatePrice(UpdatePackagePriceRequest $request, string $tier): RedirectResponse
    {
        $data = $request->validated();
        $this->packages->setPrice($request->user(), $tier, $data['monthly_cents'], $data['annual_cents']);
        return back()->with('success', 'Pricing updated.');
    }

    public function updateFeature(Request $request, string $tier): RedirectResponse
    {
        $data = $request->validate([
            'feature_key' => 'required|string|max:100',
            'enabled'     => 'required|boolean',
        ]);
        $this->packages->setFeature($request->user(), $tier, $data['feature_key'], $data['enabled']);
        return back()->with('success', 'Feature toggle saved.');
    }

    public function updateLimits(Request $request, string $tier): RedirectResponse
    {
        $data = $request->validate(['limits' => 'required|array']);
        $this->packages->setLimits($request->user(), $tier, $data['limits']);
        return back()->with('success', 'Limits saved.');
    }
}
