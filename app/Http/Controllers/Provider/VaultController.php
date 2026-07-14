<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Enums\ActivitySeverity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vault\SetVaultPermissionsRequest;
use App\Http\Requests\Vault\UploadVaultItemRequest;
use App\Models\PlanSteward;
use App\Models\VaultItem;
use App\Services\ActivityService;
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
        private ActivityService $activity,
    ) {}

    public function index(Request $request): Response
    {
        $user  = $request->user();
        $plan  = $this->plans->getForPractitioner($user->id);
        $items = $this->vault->getForPractitioner($user->id);

        $stewards = $plan
            ? \App\Models\PlanSteward::where('plan_id', $plan->id)
                ->whereIn('status', ['active', 'invited'])
                ->with('steward')
                ->get()
                ->map(fn ($ps) => [
                    'id'              => $ps->steward_id,
                    'display_name'    => $ps->steward?->display_name ?? 'Unknown',
                    'avatar_initials' => $ps->steward?->avatar_initials ?? '??',
                    'role_label'      => ucfirst(is_object($ps->role) ? $ps->role->value : ($ps->role ?? 'primary')) . ' '
                                        . ($ps->steward_category === 'continuity_steward' ? 'Continuity Steward' : 'Support Steward'),
                    'vault_access'    => is_object($ps->vault_access) ? $ps->vault_access->value : ($ps->vault_access ?? 'none'),
                ])
                ->values()
            : collect();

        return Inertia::render('Provider/Vault', [
            'zones' => [
                'standard'    => $items->filter(fn ($i) => $i->zone->value === 'standard')->values(),
                'emergency'   => $items->filter(fn ($i) => $i->zone->value === 'emergency')->values(),
                'credentials' => $items->filter(fn ($i) => $i->zone->value === 'credentials')->values(),
                'roster'      => $items->filter(fn ($i) => $i->zone->value === 'roster')->values(),
            ],
            'planStatus' => $plan?->status?->value ?? 'none',
            'attestedAt' => $plan?->vault_attested_at,
            'attestNote' => $plan?->vault_attestation_note ?? '',
            'totalCount' => $items->count(),
            'stewards'   => $stewards,
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

        $user = $request->user();

        if ($request->boolean('clear')) {
            $plan->update(['vault_attested_at' => null, 'vault_attestation_note' => null]);

            // Actor log — no steward notification for clear (deliberate silence)
            $this->activity->log(
                $user->id, 'provider', 'vault',
                ActivitySeverity::Info,
                'vault_attestation_cleared',
                'Vault attestation cleared',
                'You cleared the vault attestation. Stewards will no longer see the vault as confirmed.',
                null, null, null, 'log', $user->id,
            );

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

    /**
     * Grant / update vault access level for one or more stewards.
     * Writes plan_stewards.vault_access — not vault_items.
     */
    public function permissions(SetVaultPermissionsRequest $request): RedirectResponse
    {
        $plan = $this->plans->getForPractitioner($request->user()->id);
        abort_if(!$plan, 404);

        $user        = $request->user();
        $stewardIds  = $request->validated()['steward_ids'] ?? [];
        $accessLevel = $request->validated()['vault_access'] ?? 'none';

        PlanSteward::where('plan_id', $plan->id)
            ->whereIn('steward_id', $stewardIds)
            ->update(['vault_access' => $accessLevel]);

        $this->activity->log(
            $user->id, 'provider', 'vault',
            ActivitySeverity::Info,
            'vault_permissions_updated',
            'Vault access permissions updated',
            "You updated vault access to \"{$accessLevel}\" for " . count($stewardIds) . ' steward(s).',
            null, null, null, 'log', $user->id,
        );

        return back()->with('success', 'Vault access updated.');
    }

    /**
     * Update a roster VaultItem (client record).
     */
    public function update(Request $request, VaultItem $item): RedirectResponse
    {
        abort_if($item->practitioner_id !== $request->user()->id, 403);

        $data = $request->validate([
            'client_name'     => 'required|string|max:191',
            'client_location' => 'nullable|string|max:191',
            'client_phone'    => 'nullable|string|max:30',
            'client_service'  => 'required|string|max:191',
            'client_priority' => 'nullable|boolean',
            'description'     => 'nullable|string|max:2000',
        ]);

        $item->update($data);

        $user = $request->user();
        $this->activity->log(
            $user->id, 'provider', 'vault',
            ActivitySeverity::Info,
            'vault_client_updated',
            'Client roster record updated',
            "You updated the roster record for \"{$item->client_name}\".",
            VaultItem::class, $item->id, null, 'log', $user->id,
        );

        return back()->with('success', 'Client updated.');
    }

    public function destroy(Request $request, VaultItem $item): RedirectResponse
    {
        abort_if($item->practitioner_id !== $request->user()->id, 403);

        $user      = $request->user();
        $itemTitle = $item->title;
        $itemId    = $item->id;

        $item->delete();

        $this->activity->log(
            $user->id, 'provider', 'vault',
            ActivitySeverity::Warning,
            'vault_item_deleted',
            'Vault item deleted',
            "You permanently deleted \"{$itemTitle}\" from the vault.",
            VaultItem::class, $itemId, null, 'log', $user->id,
        );

        return back()->with('success', 'Vault item deleted.');
    }
}
