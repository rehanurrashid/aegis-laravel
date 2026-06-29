<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CeuEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CeuService
{
    public function create(User $practitioner, array $data): CeuEntry
    {
        return CeuEntry::create([
            'id'              => 'ceu_' . Str::lower(Str::random(12)),
            'practitioner_id' => $practitioner->id,
            'title'           => $data['title'],
            'provider_name'   => $data['provider_name'] ?? null,
            'credit_hours'    => $data['credit_hours'] ?? 0,
            'completed_on'    => $data['completed_on'] ?? now()->toDateString(),
            'certificate_ref' => $data['certificate_ref'] ?? null,
            'created_at'      => now(),
        ]);
    }

    public function update(CeuEntry $ceu, array $data): CeuEntry
    {
        $allowed = ['title', 'provider_name', 'credit_hours', 'completed_on', 'expires_on', 'certificate_ref'];
        $ceu->update(array_intersect_key($data, array_flip($allowed)));
        return $ceu->fresh();
    }

    public function delete(CeuEntry $ceu): bool
    {
        return (bool) $ceu->delete();
    }

    public function getForPractitioner(string $practitionerId): Collection
    {
        return CeuEntry::where('practitioner_id', $practitionerId)
            ->orderByDesc('completed_on')
            ->get();
    }

    public function getProgress(string $practitionerId, ?int $year = null): array
    {
        $year = $year ?? (int) date('Y');
        $rows = CeuEntry::where('practitioner_id', $practitionerId)
            ->whereYear('completed_on', $year)
            ->get();

        $total = $rows->sum('credit_hours');

        return [
            'year'  => $year,
            'total' => $total,
            'count' => $rows->count(),
        ];
    }
}
