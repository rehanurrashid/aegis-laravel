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

    public function upload(User $practitioner, ?UploadedFile $file, string $zone, array $meta = []): VaultItem
    {
        $allowed = ['standard', 'emergency', 'credentials', 'roster'];
        if (!in_array($zone, $allowed, true)) {
            throw new RuntimeException("Invalid vault zone: {$zone}");
        }

        $path     = null;
        $fileName = null;
        $fileSize = null;
        $mimeType = null;

        if ($file !== null) {
            $disk     = config('filesystems.default') === 's3' ? 's3' : 'local';
            $path     = "vault/{$practitioner->id}/" . Str::lower(Str::random(16)) . '.' . $file->getClientOriginalExtension();
            Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));
            $fileName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();
        }

        // Encrypt credential password before storage
        $credPassword = null;
        if (!empty($meta['credential_password'])) {
            $credPassword = encrypt($meta['credential_password']);
        }

        $zoneLabel = $zone;
        $title     = $meta['title'] ?? ($fileName ?? 'Untitled');
        $item = VaultItem::create([
            'id'                      => 'vi_' . Str::lower(Str::random(12)),
            'practitioner_id'         => $practitioner->id,
            'zone'                    => $zone,
            'category'                => $meta['category'] ?? null,
            'title'                   => $title,
            'sub_label'               => $meta['sub_label'] ?? null,
            'description'             => $meta['description'] ?? null,
            'status'                  => $zone === 'credentials' ? 'vault_only' : ($zone === 'roster' ? ($meta['client_priority'] ? 'priority' : 'active') : 'active'),
            'file_name'               => $fileName,
            'file_size'               => $fileSize,
            'mime_type'               => $mimeType,
            's3_key'                  => $path,
            'file_ref'                => $path,
            'issued_at'               => $meta['issued_at'] ?? null,
            'expires_at'              => $meta['expires_at'] ?? null,
            // Credential fields
            'credential_username'     => $meta['credential_username'] ?? null,
            'credential_password_enc' => $credPassword,
            'credential_url'          => $meta['credential_url'] ?? null,
            // Roster fields
            'client_name'             => $meta['client_name'] ?? null,
            'client_location'         => $meta['client_location'] ?? null,
            'client_phone'            => $meta['client_phone'] ?? null,
            'client_service'          => $meta['client_service'] ?? null,
            'client_priority'         => $meta['client_priority'] ? 1 : 0,
            'client_notes'            => $meta['client_notes'] ?? null,
            'created_at'              => now(),
        ]);

        $this->activity->log(
            $practitioner->id, 'provider',
            'vault', ActivitySeverity::Info,
            'vault_item_uploaded', 'Vault item uploaded',
            "You uploaded \"{$title}\" to the {$zoneLabel} vault zone.",
            VaultItem::class, $item->id, null, 'log', $practitioner->id,
        );

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
                VaultItem::class,
                $item->id,
                $sharer->id,
                'notification',
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
