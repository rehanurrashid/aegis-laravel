<?php

declare(strict_types=1);

namespace App\Http\Controllers\ContinuitySteward;

use App\Http\Controllers\Controller;
use App\Models\ContinuityPlan;
use App\Models\VaultItem;
use App\Services\VaultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VaultController extends Controller
{
    public function __construct(private VaultService $vault) {}

    public function index(Request $request, ContinuityPlan $plan): Response
    {
        $items = $this->vault->getContents($plan->id, $request->user());

        return Inertia::render('ContinuitySteward/Vault', [
            'plan'       => $plan,
            'items'      => $items,
            'planStatus' => $plan->status,
        ]);
    }

    public function download(Request $request, ContinuityPlan $plan, VaultItem $item): RedirectResponse
    {
        abort_if($item->practitioner_id !== $plan->practitioner_id, 404);
        $this->vault->getContents($plan->id, $request->user()); // gate check
        $this->vault->logAccess($item, $request->user());
        return redirect()->away($this->vault->signedDownloadUrl($item));
    }
}
