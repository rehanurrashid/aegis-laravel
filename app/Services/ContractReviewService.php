<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivityPortal;
use App\Enums\ActivitySeverity;
use App\Enums\UserRole;
use App\Events\Business\ContractReviewSubmitted;
use App\Models\BpContract;
use App\Models\BpContractReview;
use App\Models\User;
use App\Models\UserMeta;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Post-completion contract reviews.
 * Enforces one review per party per contract via the DB unique constraint.
 * Recomputes the aggregate BP rating in user_meta after each review submission.
 */
class ContractReviewService
{
    public function __construct(private ActivityService $activity) {}

    /**
     * Submit a review. Throws if already reviewed or contract not completed.
     */
    public function create(BpContract $contract, User $reviewer, array $data): BpContractReview
    {
        $statusVal = $contract->status instanceof \BackedEnum
            ? $contract->status->value
            : (string) $contract->status;

        if ($statusVal !== 'completed') {
            throw new RuntimeException('Reviews can only be submitted on completed contracts.');
        }

        if ($reviewer->id !== $contract->practitioner_id && $reviewer->id !== $contract->bp_id) {
            throw new RuntimeException('Only the parties to this contract may leave a review.');
        }

        $alreadyReviewed = BpContractReview::where('contract_id', $contract->id)
            ->where('reviewer_id', $reviewer->id)
            ->exists();

        if ($alreadyReviewed) {
            throw new RuntimeException('You have already submitted a review for this contract.');
        }

        $revieweeId = $reviewer->id === $contract->practitioner_id
            ? $contract->bp_id
            : $contract->practitioner_id;

        $review = BpContractReview::create([
            'id'          => 'rev_' . Str::lower(Str::random(12)),
            'contract_id' => $contract->id,
            'reviewer_id' => $reviewer->id,
            'reviewee_id' => $revieweeId,
            'rating'      => (int) ($data['rating'] ?? 0),
            'communication' => (int) ($data['communication'] ?? 0),
            'quality'     => (int) ($data['quality'] ?? 0),
            'timeliness'  => (int) ($data['timeliness'] ?? 0),
            'review_text' => $data['review_text'] ?? null,
            'is_public'   => (bool) ($data['is_public'] ?? true),
        ]);

        // Recompute aggregate rating for the reviewee (only public reviews)
        $this->recomputeAggregateRating($revieweeId);

        // Actor log
        $reviewee       = User::find($revieweeId);
        $reviewerPortal = $reviewer->role instanceof UserRole
            ? ActivityPortal::fromUserRole($reviewer->role)->value
            : 'provider';
        $revieweePortal = $reviewee?->role instanceof UserRole
            ? ActivityPortal::fromUserRole($reviewee->role)->value
            : 'provider';

        $this->activity->log(
            $reviewer->id,
            $reviewerPortal,
            'job_postings',
            ActivitySeverity::Info,
            'contract_review_submitted',
            "Review submitted for {$reviewee?->display_name}",
            "Rating: {$review->rating}/5 — {$contract->title}",
            'bp_contract_review',
            $review->id,
            $revieweeId,
            'log',
            $reviewer->id
        );

        // Notification → reviewee
        $this->activity->log(
            $revieweeId,
            $revieweePortal,
            'job_postings',
            ActivitySeverity::Info,
            'review_received',
            "{$reviewer->display_name} left you a {$review->rating}-star review",
            $data['review_text'] ? Str::limit($data['review_text'], 100) : 'Review submitted after contract completion.',
            'bp_contract_review',
            $review->id,
            $reviewer->id,
            'notification',
            $reviewer->id
        );

        event(new ContractReviewSubmitted($review));

        return $review;
    }

    /**
     * Mark a review prompt as dismissed (user skipped without reviewing).
     */
    public function dismiss(BpContract $contract, User $user): void
    {
        BpContractReview::updateOrCreate(
            ['contract_id' => $contract->id, 'reviewer_id' => $user->id],
            [
                'id'               => 'rev_' . Str::lower(Str::random(12)),
                'reviewee_id'      => $user->id === $contract->practitioner_id
                    ? $contract->bp_id
                    : $contract->practitioner_id,
                'review_dismissed' => true,
                'is_public'        => false,
                'rating'           => 0,
            ]
        );
    }

    /**
     * Fetch public reviews for a user's profile display.
     * Returns most recent first, capped at $limit.
     */
    public function getPublicReviews(User $user, int $limit = 10): array
    {
        return BpContractReview::where('reviewee_id', $user->id)
            ->where('is_public', true)
            ->where('review_dismissed', false)
            ->where('rating', '>', 0)
            ->with('reviewer:id,display_name,credentials,role')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (BpContractReview $r) => [
                'id'            => $r->id,
                'rating'        => $r->rating,
                'communication' => $r->communication,
                'quality'       => $r->quality,
                'timeliness'    => $r->timeliness,
                'review_text'   => $r->review_text,
                'reviewer_name' => $r->reviewer?->display_name ?? 'Anonymous',
                'reviewer_role' => $r->reviewer?->role instanceof \BackedEnum
                    ? $r->reviewer->role->value
                    : (string) ($r->reviewer?->role ?? ''),
                'reviewer_credentials' => $r->reviewer?->credentials,
                'created_at'    => $r->created_at?->format('M Y'),
            ])
            ->toArray();
    }

    /**
     * Get aggregate stats for a user's reviews.
     */
    public function getAggregateStats(User $user): array
    {
        $reviews = BpContractReview::where('reviewee_id', $user->id)
            ->where('is_public', true)
            ->where('review_dismissed', false)
            ->where('rating', '>', 0)
            ->get();

        if ($reviews->isEmpty()) {
            return ['avg_rating' => null, 'review_count' => 0, 'breakdown' => []];
        }

        return [
            'avg_rating'    => round($reviews->avg('rating'), 1),
            'communication' => round($reviews->avg('communication'), 1),
            'quality'       => round($reviews->avg('quality'), 1),
            'timeliness'    => round($reviews->avg('timeliness'), 1),
            'review_count'  => $reviews->count(),
        ];
    }

    /**
     * Check if a user has already reviewed (or dismissed) a contract.
     */
    public function hasReviewed(BpContract $contract, User $user): bool
    {
        return BpContractReview::where('contract_id', $contract->id)
            ->where('reviewer_id', $user->id)
            ->exists();
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function recomputeAggregateRating(string $userId): void
    {
        $avg = BpContractReview::where('reviewee_id', $userId)
            ->where('is_public', true)
            ->where('review_dismissed', false)
            ->where('rating', '>', 0)
            ->avg('rating');

        if ($avg === null) return;

        $rounded = (string) round((float) $avg, 1);

        // Persist to user_meta so public profile reads are fast
        $existing = UserMeta::where('user_id', $userId)
            ->where('meta_key', 'bp_avg_rating')
            ->first();

        if ($existing) {
            $existing->update(['meta_value' => $rounded]);
        } else {
            UserMeta::create([
                'id'         => 'um_' . Str::lower(Str::random(12)),
                'user_id'    => $userId,
                'meta_key'   => 'bp_avg_rating',
                'meta_value' => $rounded,
                'meta_type'  => 'string',
            ]);
        }
    }
}
