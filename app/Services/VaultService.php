<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\Plan\VaultItemShared;

use App\Enums\ActivitySeverity;
use App\Models\ContinuityPlan;
use App\Models\PlanSteward;
use App\Models\User;
use App\Models\VaultItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class VaultService
{
    public function __construct(
        private ActivityService $activity,
        private IncidentService $incidents
    ) {}

    public function upload(User $practitioner, UploadedFile $file, string $zone, array $meta = []): VaultItem
    {
        $allowed = ['standard', 'emergency', 'credentials', 'roster'];
        if (!in_array($zone, $allowed, true)) {
            throw new RuntimeException("Invalid vault zone: {$zone}");
        }

        $disk = config('filesystems.default') === 's3' ? 's3' : 'local';
        $path = "vault/{$practitioner->id}/" . Str::lower(Str::random(16)) . '.' . $file->getClientOriginalExtension();
        Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));

        $item = VaultItem::create([
            'id'              => 'vi_' . Str::lower(Str::random(12)),
            'practitioner_id' => $practitioner->id,
            'zone'            => $zone,
            'title'           => $meta['title'] ?? $file->getClientOriginalName(),
            'description'     => $meta['description'] ?? null,
            'file_name'       => $file->getClientOriginalName(),
            'file_size'       => $file->getSize(),
            'mime_type'       => $file->getMimeType(),
            's3_key'          => $path,
            'created_at'      => now(),
        ]);

        return $item;
    }

    public function getForPractitioner(string $practitionerId): Collection
    {
        return VaultItem::where('practitioner_id', $practitionerId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * CS-side gated read — requires active incident AND requesting user is assigned steward.
     */
    public function getContents(string $planId, User $requester): Collection
    {
        $plan = ContinuityPlan::findOrFail($planId);

        if (!$this->incidents->hasActiveForPlan($planId)) {
            abort(403, 'Vault is sealed: no active critical incident.');
        }

        $isSteward = PlanSteward::where('plan_id', $planId)
            ->where('steward_id', $requester->id)
            ->where('status', 'active')
            ->exists();
        if (!$isSteward) {
            abort(403, 'You are not an assigned steward for this plan.');
        }

        return VaultItem::where('practitioner_id', $plan->practitioner_id)->get();
    }

    public function setPermissions(VaultItem $item, array $stewardIds): void
    {
        // Stored as JSON on the item for the prototype; in Laravel a pivot table is preferable.
        $item->update(['permitted_steward_ids' => json_encode($stewardIds)]);
    }

    public function signedDownloadUrl(VaultItem $item): string
    {
        $disk = config('filesystems.default') === 's3' ? 's3' : 'local';

        if ($disk === 's3') {
            return Storage::disk('s3')->temporaryUrl($item->s3_key, now()->addMinutes(15));
        }

        // Local fallback — returns route-based URL valid via signed middleware
        return url('/vault/local-download/' . $item->id . '?expires=' . now()->addMinutes(15)->timestamp);
    }

    public function share(VaultItem $item, array $stewardIds, User $sharer): void
    {
        $this->setPermissions($item, $stewardIds);

        foreach ($stewardIds as $sid) {
            $this->activity->log(
                $sid,
                'continuity_steward',
                'vault',
                ActivitySeverity::Info,
                'vault_shared',
                "{$sharer->display_name} shared a vault item with you",
                $item->title,
                'vault_item',
                $item->id,
                $sharer->id
            );
        }

        event(new VaultItemShared($item, $stewardIds, $sharer));
    }

    /**
     * Always called after a CS/SS vault read.
     */
    public function logAccess(VaultItem $item, User $user): void
    {
        \App\Models\VaultAccessLog::create([
            'id'           => 'val_' . Str::lower(Str::random(12)),
            'vault_item_id'=> $item->id,
            'user_id'      => $user->id,
            'accessed_at'  => now(),
        ]);
    }

    public function seal(string $planId): void
    {
        // Sealing is implicit — driven by absence of active incident; nothing to update.
        // Hook left for future explicit-seal logic / cache invalidation.
    }

    public function unseal(string $planId): void
    {
        // Unsealing is also implicit — driven by IncidentService::activate().
    }
}
