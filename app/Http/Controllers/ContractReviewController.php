<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BpContract;
use App\Services\ContractReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles post-completion contract reviews.
 * Used by both Provider (provider.jobs.contract.review)
 * and BP (bp.contracts.review) portals.
 */
class ContractReviewController extends Controller
{
    public function __construct(private ContractReviewService $reviews) {}

    /**
     * Submit a review for a completed contract.
     * Works for both provider → BP and BP → provider directions.
     */
    public function store(Request $request, BpContract $contract): RedirectResponse
    {
        $user = $request->user();

        // Gate: must be a party to this contract
        if ($user->id !== $contract->practitioner_id && $user->id !== $contract->bp_id) {
            abort(403);
        }

        $data = $request->validate([
            'rating'        => 'required|integer|min:1|max:5',
            'communication' => 'required|integer|min:1|max:5',
            'quality'       => 'required|integer|min:1|max:5',
            'timeliness'    => 'required|integer|min:1|max:5',
            'review_text'   => 'nullable|string|max:1000',
            'is_public'     => 'boolean',
        ]);

        try {
            $this->reviews->create($contract, $user, $data);
            return back()->with('success', 'Review submitted. Thank you for your feedback.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['review' => $e->getMessage()]);
        }
    }

    /**
     * Dismiss the review prompt without submitting a review.
     */
    public function dismiss(Request $request, BpContract $contract): RedirectResponse
    {
        $user = $request->user();

        if ($user->id !== $contract->practitioner_id && $user->id !== $contract->bp_id) {
            abort(403);
        }

        $this->reviews->dismiss($contract, $user);
        return back()->with('success', 'Review skipped.');
    }
}
