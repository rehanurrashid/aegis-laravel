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
            'provider'        => $data['provider'] ?? null,
            'category'        => $data['category'] ?? null,
            'credits'         => $data['credits'] ?? 0,
            'completed_at'    => $data['completed_at'] ?? now(),
            'cert_url'        => $data['cert_url'] ?? null,
            'created_at'      => now(),
        ]);
    }

    public function update(CeuEntry $ceu, array $data): CeuEntry
    {
        $allowed = ['title', 'provider', 'category', 'credits', 'completed_at', 'cert_url'];
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
            ->orderByDesc('completed_at')
            ->get();
    }

    public function getProgress(string $practitionerId, ?int $year = null): array
    {
        $year = $year ?? (int) date('Y');
        $rows = CeuEntry::where('practitioner_id', $practitionerId)
            ->whereYear('completed_at', $year)
            ->get();

        $total = $rows->sum('credits');
        $byCategory = $rows->groupBy('category')->map->sum('credits')->toArray();

        return [
            'year'        => $year,
            'total'       => $total,
            'by_category' => $byCategory,
            'count'       => $rows->count(),
        ];
    }
}
