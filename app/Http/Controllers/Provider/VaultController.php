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
        $user  = $request->user();
        $plan  = $this->plans->getForPractitioner($user->id);
        $items = $this->vault->getForPractitioner($user->id);

        // Stewards with vault_access info for the permissions panel
        $stewards = $plan
            ? \App\Models\PlanSteward::where('plan_id', $plan->id)
                ->whereIn('status', ['active', 'invited'])
                ->with('steward')
                ->get()
                ->map(fn ($ps) => [
                    'id'            => $ps->steward_id,
                    'display_name'  => $ps->steward?->display_name ?? 'Unknown',
                    'avatar_initials'=> $ps->steward?->avatar_initials ?? '??',
                    'role_label'    => ucfirst($ps->role ?? 'primary') . ' ' . ($ps->steward_category === 'continuity_steward' ? 'Continuity Steward' : 'Support Steward'),
                    'vault_access'  => $ps->vault_access ?? 'none',
                ])
                ->values()
            : collect();

        // Calculate storage (placeholder until S3 usage API is integrated)
        $storageUsed    = '1.2 GB';
        $storageTotal   = '10 GB';
        $storagePercent = 12;

        return Inertia::render('Provider/Vault', [
            'zones' => [
                'standard'    => $items->where('zone', 'standard')->values(),
                'emergency'   => $items->where('zone', 'emergency')->values(),
                'credentials' => $items->where('zone', 'credentials')->values(),
                'roster'      => $items->where('zone', 'roster')->values(),
            ],
            'planStatus'     => $plan?->status?->value ?? 'none',
            'attestedAt'     => $plan?->vault_attested_at,
            'attestNote'     => $plan?->vault_attestation_note ?? '',
            'totalCount'     => $items->count(),
            'stewards'       => $stewards,
            'storageUsed'    => $storageUsed,
            'storageTotal'   => $storageTotal,
            'storagePercent' => $storagePercent,
        ]);
    }

    public function upload(UploadVaultItemRequest $request): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan, 404);
        $this->authorize('upload', \App\Models\VaultItem::class);

        $data = $request->validated();
        $this->vault->upload($request->user(), $request->file('file') ?? null, $data['zone'], $data);
        return back()->with('success', 'Item saved to vault.');
    }

    public function attest(\App\Http\Requests\Plan\AttestVaultRequest $request): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan, 404);
        $this->authorize('attestVault', $plan);

        if ($request->boolean('clear')) {
            $plan->update(['vault_attested_at' => null, 'vault_attestation_note' => null]);
            return back()->with('success', 'Attestation cleared.');
        }

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
