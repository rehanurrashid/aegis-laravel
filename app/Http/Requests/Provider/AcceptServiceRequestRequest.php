<?php

declare(strict_types=1);

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Wave 3 — Accept a service request.
 *
 * New in Wave 3: optional negotiated_amount_cents allows the provider
 * to offer a different price from the listing when accepting.
 * The client is notified to pay the negotiated deposit.
 */
class AcceptServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via Policy::manage
    }

    public function rules(): array
    {
        return [
            'session_date'            => 'nullable|date|after_or_equal:today',
            'session_time'            => 'nullable|string|max:10',
            'timezone'                => 'nullable|string|max:64',
            'format'                  => 'nullable|string|in:Virtual (Telehealth),In-person,telehealth,in_person,both',
            'note'                    => 'nullable|string|max:1000',
            'recurring'               => 'nullable|boolean',
            // Wave 3 addition: price negotiation
            // null = use listing price; 0 = free session; positive int = custom price
            'negotiated_amount_cents'    => 'nullable|integer|min:0|max:9999999',
            // Rev 4: provider may counter the client's proposed terms at accept time
            'terms_countered'              => 'nullable|boolean',
            'committed_payment_structure'  => 'nullable|in:full_upfront,split,full_on_completion',
            'committed_upfront_percentage' => 'nullable|integer|min:1|max:99',
            'committed_terms_note'         => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'session_date.after_or_equal'          => 'The session date must be today or in the future.',
            'negotiated_amount_cents.integer'       => 'The negotiated price must be a whole number (cents).',
            'negotiated_amount_cents.min'           => 'The negotiated price cannot be negative.',
            'negotiated_amount_cents.max'           => 'The negotiated price cannot exceed $99,999.',
        ];
    }
}
