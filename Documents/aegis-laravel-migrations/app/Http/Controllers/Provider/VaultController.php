<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vault\SetVaultPermissionsRequest;
use App\Http\Requests\Vault\UploadVaultItemRequest;
use App\Models\VaultItem;
use App\Services\PlanService;
use App\Services\VaultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VaultController extends Controller
{
    public function __construct(
        private VaultService $vault,
        private PlanService $plans,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $plan = $this->plans->getForPractitioner($user->id);
        $items = $this->vault->getForPractitioner($user->id);

        return Inertia::render('Provider/Vault', [
            'zones' => [
                'credentials' => $items->where('zone', 'credentials')->values(),
                'roster'      => $items->where('zone', 'roster')->values(),
                'emergency'   => $items->where('zone', 'emergency')->values(),
                'standard'    => $items->where('zone', 'standard')->values(),
            ],
            'planStatus' => $plan?->status ?? 'none',
            'attestedAt' => $plan?->vault_attested_at,
            'totalCount' => $items->count(),
        ]);
    }

    public function upload(UploadVaultItemRequest $request): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan, 404);
        $this->authorize('upload', \App\Models\VaultItem::class);

        $data = $request->validated();
        $this->vault->upload($request->user(), $request->file('file'), $data['zone'], [
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
        ]);
        return back()->with('success', 'Document uploaded.');
    }

    public function attest(\App\Http\Requests\Plan\AttestVaultRequest $request): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan, 404);
        $this->authorize('attestVault', $plan);

        $this->plans->attestVault($plan, $request->input('note'));
        return back()->with('success', 'Vault attested.');
    }

    public function download(Request $request, VaultItem $item): RedirectResponse
    {
        abort_if($item->practitioner_id !== $request->user()->id, 403);
        return redirect()->away($this->vault->signedDownloadUrl($item));
    }

    public function permissions(SetVaultPermissionsRequest $request, VaultItem $item): RedirectResponse
    {
        abort_if($item->practitioner_id !== $request->user()->id, 403);
        $this->vault->setPermissions($item, $request->validated()['steward_ids'] ?? []);
        return back()->with('success', 'Permissions updated.');
    }

    public function destroy(Request $request, VaultItem $item): RedirectResponse
    {
        abort_if($item->practitioner_id !== $request->user()->id, 403);
        $item->delete();
        return back()->with('success', 'Vault item deleted.');
    }
}
