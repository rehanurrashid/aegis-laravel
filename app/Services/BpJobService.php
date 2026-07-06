<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Models\BpJob;
use App\Models\BpSavedJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class BpJobService
{
    public function __construct(private ActivityService $activity) {}

    public function create(User $practitioner, array $data): BpJob
    {
        $job = BpJob::create([
            'id'                    => 'bj_' . Str::lower(Str::random(12)),
            'practitioner_id'       => $practitioner->id,
            'title'                 => $data['title'],
            'description'           => $data['description'] ?? null,
            'category'              => $data['category'] ?? null,
            'job_type'              => $data['job_type'] ?? null,
            'experience_level'      => $data['experience_level'] ?? null,
            'partner_type_pref'     => $data['partner_type_pref'] ?? null,
            'tags'                  => $data['tags'] ?? null,
            'certifications'        => $data['certifications'] ?? null,
            'requires_hipaa'        => $data['requires_hipaa'] ?? false,
            'requires_nda'          => $data['requires_nda'] ?? false,
            'requires_baa'          => $data['requires_baa'] ?? false,
            'application_deadline'  => $data['application_deadline'] ?? null,
            'max_applicants'        => $data['max_applicants'] ?? 0,
            'budget_amount_cents'   => $data['budget_amount_cents'] ?? null,
            'budget_type'           => $data['budget_type'] ?? 'fixed',
            'location_pref'         => $data['location_pref'] ?? null,
            'payment_method'        => $data['payment_method'] ?? null,
            'billing_frequency'     => $data['billing_frequency'] ?? null,
            'perks'                 => $data['perks'] ?? null,
            'is_featured'           => $data['is_featured'] ?? false,
            'is_urgent'             => $data['is_urgent'] ?? false,
            'internal_notes'        => $data['internal_notes'] ?? null,
            'start_date'            => $data['start_date'] ?? null,
            'status'                => $data['status'] ?? 'open',
            'posted_at'             => now(),
            'closes_at'             => $data['application_deadline'] ?? null,
        ]);

        // Actor log — provider's history ("I posted a new job")
        if (($data['status'] ?? 'open') === 'open') {
            $this->activity->log(
                $practitioner->id, 'provider', 'job_postings', ActivitySeverity::Info,
                'job_posted',
                "Job posted: {$job->title}",
                'Your posting is now live to Business Partners on Aegis.',
                'bp_job', $job->id, null,
                'log', $practitioner->id
            );
        }

        return $job;
    }

    public function update(BpJob $job, array $data): BpJob
    {
        $job->update([
            'title'                 => $data['title'],
            'category'              => $data['category'],
            'job_type'              => $data['job_type'] ?? $job->job_type,
            'location_pref'         => $data['location_pref'] ?? $job->location_pref,
            'start_date'            => $data['start_date'] ?? $job->start_date,
            'tags'                  => $data['tags'] ?? $job->tags,
            'description'           => $data['description'],
            'experience_level'      => $data['experience_level'] ?? $job->experience_level,
            'partner_type_pref'     => $data['partner_type_pref'] ?? $job->partner_type_pref,
            'certifications'        => $data['certifications'] ?? $job->certifications,
            'requires_hipaa'        => $data['requires_hipaa'] ?? $job->requires_hipaa,
            'requires_nda'          => $data['requires_nda'] ?? $job->requires_nda,
            'requires_baa'          => $data['requires_baa'] ?? $job->requires_baa,
            'application_deadline'  => $data['application_deadline'] ?? $job->application_deadline,
            'max_applicants'        => $data['max_applicants'] ?? $job->max_applicants,
            'budget_type'           => $data['budget_type'] ?? $job->budget_type,
            'budget_amount_cents'   => $data['budget_amount_cents'] ?? $job->budget_amount_cents,
            'payment_method'        => $data['payment_method'] ?? $job->payment_method,
            'billing_frequency'     => $data['billing_frequency'] ?? $job->billing_frequency,
            'perks'                 => $data['perks'] ?? $job->perks,
            'is_featured'           => $data['is_featured'] ?? $job->is_featured,
            'is_urgent'             => $data['is_urgent'] ?? $job->is_urgent,
            'internal_notes'        => $data['internal_notes'] ?? $job->internal_notes,
            'status'                => $data['status'],
        ]);

        $this->activity->log(
            $job->practitioner_id, 'provider', 'job_postings', ActivitySeverity::Info,
            'job_updated', "Posting updated: {$job->title}",
            'You updated the details of this job posting.',
            'bp_job', $job->id, null, 'log', $job->practitioner_id
        );

        return $job->fresh();
    }

    public function setStatus(BpJob $job, string $status): BpJob
    {
        $previous = $job->status instanceof \BackedEnum ? $job->status->value : (string) $job->status;
        $job->update(['status' => $status]);

        $labelMap = [
            'open'      => 'Published (live to Business Partners)',
            'paused'    => 'Paused (hidden from marketplace)',
            'closed'    => 'Closed',
            'cancelled' => 'Cancelled',
            'filled'    => 'Filled',
        ];

        // Actor log — provider's status change history
        $this->activity->log(
            $job->practitioner_id, 'provider', 'job_postings', ActivitySeverity::Info,
            'job_status_changed',
            "Posting status updated: {$job->title}",
            ($labelMap[$previous] ?? ucfirst($previous)) . ' → ' . ($labelMap[$status] ?? ucfirst($status)),
            'bp_job', $job->id, null,
            'log', $job->practitioner_id
        );

        return $job->fresh();
    }

    public function close(BpJob $job): BpJob
    {
        $job->update(['status' => 'closed']);

        // Actor log — provider's history ("I closed this posting")
        $this->activity->log(
            $job->practitioner_id, 'provider', 'job_postings', ActivitySeverity::Info,
            'job_closed',
            "Posting closed: {$job->title}",
            'This posting is no longer visible to Business Partners.',
            'bp_job', $job->id, null,
            'log', $job->practitioner_id
        );

        return $job->fresh();
    }

    public function fill(BpJob $job): BpJob
    {
        $job->update(['status' => 'filled']);

        $this->activity->log(
            $job->practitioner_id, 'provider', 'job_postings', ActivitySeverity::Info,
            'job_filled', "Posting marked filled: {$job->title}",
            'This posting has been marked as filled.',
            'bp_job', $job->id, null, 'log', $job->practitioner_id
        );

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
