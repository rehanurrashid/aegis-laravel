<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Models\CeuEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CeuService
{
    public function __construct(private ActivityService $activity) {}

    public function create(User $practitioner, array $data, ?UploadedFile $certificateFile = null): CeuEntry
    {
        $certRef = $this->storeCertificate($practitioner->id, $certificateFile)
            ?? ($data['certificate_ref'] ?? null);

        $entry = CeuEntry::create([
            'id'              => 'ceu_' . Str::lower(Str::random(12)),
            'practitioner_id' => $practitioner->id,
            'title'           => $data['title'],
            'provider_name'   => $data['provider_name'] ?? null,
            'credit_hours'    => $data['credit_hours'] ?? 0,
            'completed_on'    => $data['completed_on'] ?? now()->toDateString(),
            'expires_on'      => $data['expires_on'] ?? null,
            'certificate_ref' => $certRef,
            'created_at'      => now(),
        ]);

        $this->activity->log(
            $practitioner->id, 'provider', 'event',
            ActivitySeverity::Info, 'ceu_logged', 'CEU entry added',
            "You logged {$entry->credit_hours} CEU credit(s) for \"{$entry->title}\".",
            CeuEntry::class, $entry->id, null, 'log', $practitioner->id,
        );

        return $entry;
    }

    public function update(CeuEntry $ceu, array $data, ?UploadedFile $certificateFile = null): CeuEntry
    {
        $certRef = $this->storeCertificate($ceu->practitioner_id, $certificateFile)
            ?? ($data['certificate_ref'] ?? $ceu->certificate_ref);

        $allowed = ['title', 'provider_name', 'credit_hours', 'completed_on', 'expires_on'];
        $updates = array_intersect_key($data, array_flip($allowed));
        $updates['certificate_ref'] = $certRef;
        $ceu->update($updates);
        return $ceu->fresh();
    }

    public function delete(CeuEntry $ceu): bool
    {
        if ($ceu->certificate_ref) {
            Storage::disk('public')->delete($ceu->certificate_ref);
        }
        return (bool) $ceu->delete();
    }

    public function getForPractitioner(string $practitionerId): Collection
    {
        return CeuEntry::where('practitioner_id', $practitionerId)
            ->orderByDesc('completed_on')->get();
    }

    public function getProgress(string $practitionerId, ?int $year = null): array
    {
        $year = $year ?? (int) date('Y');
        $rows = CeuEntry::where('practitioner_id', $practitionerId)
            ->whereYear('completed_on', $year)->get();
        return ['year' => $year, 'total' => $rows->sum('credit_hours'), 'count' => $rows->count()];
    }

    private function storeCertificate(string $practitionerId, ?UploadedFile $file): ?string
    {
        if (!$file) return null;
        $name = Str::lower(Str::random(16)) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs("ceu/{$practitionerId}", $name, 'public');
        return $path ?: null;
    }

    public static function certificateUrl(?string $ref): ?string
    {
        if (!$ref) return null;
        return Storage::disk('public')->url($ref);
    }
}
