<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BpJob;
use App\Models\BpSavedJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class BpJobService
{
    public function create(User $practitioner, array $data): BpJob
    {
        return BpJob::create([
            'id'                => 'bj_' . Str::lower(Str::random(12)),
            'practitioner_id'   => $practitioner->id,
            'title'             => $data['title'],
            'description'       => $data['description'] ?? null,
            'category'          => $data['category'] ?? null,
            'budget_min_cents'  => $data['budget_min_cents'] ?? null,
            'budget_max_cents'  => $data['budget_max_cents'] ?? null,
            'budget_type'       => $data['budget_type'] ?? 'fixed',
            'status'            => 'open',
            'posted_at'         => now(),
            'expires_at'        => $data['expires_at'] ?? now()->addDays(30),
        ]);
    }

    public function close(BpJob $job): BpJob
    {
        $job->update(['status' => 'closed', 'closed_at' => now()]);
        return $job->fresh();
    }

    public function fill(BpJob $job): BpJob
    {
        $job->update(['status' => 'filled', 'filled_at' => now()]);
        return $job->fresh();
    }

    public function expire(BpJob $job): BpJob
    {
        $job->update(['status' => 'expired']);
        return $job->fresh();
    }

    public function browse(array $filters = []): Collection
    {
        $query = BpJob::where('status', 'open');
        if (!empty($filters['category']))    $query->where('category', $filters['category']);
        if (!empty($filters['budget_type'])) $query->where('budget_type', $filters['budget_type']);
        if (!empty($filters['search']))      $query->where('title', 'like', '%' . $filters['search'] . '%');

        return $query->orderByDesc('posted_at')->limit($filters['limit'] ?? 50)->get();
    }

    public function save(BpJob $job, User $bp): BpSavedJob
    {
        return BpSavedJob::firstOrCreate(
            ['job_id' => $job->id, 'bp_id' => $bp->id],
            ['id' => 'bjs_' . Str::lower(Str::random(12)), 'saved_at' => now()]
        );
    }

    public function unsave(BpJob $job, User $bp): bool
    {
        return BpSavedJob::where('job_id', $job->id)
            ->where('bp_id', $bp->id)
            ->delete() > 0;
    }
}
