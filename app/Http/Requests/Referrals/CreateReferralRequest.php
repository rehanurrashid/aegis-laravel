<?php

declare(strict_types=1);

namespace App\Http\Requests\Referrals;

use Illuminate\Foundation\Http\FormRequest;

class CreateReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Step 1 — client
            'roster_item_id'       => ['nullable', 'string', 'exists:vault_items,id'],
            'client_name'          => ['required', 'string', 'max:191'],
            'diagnosis'            => ['nullable', 'string', 'max:191'],

            // Step 2 — practitioner
            'provider_slug'        => ['nullable', 'string', 'max:64'],
            'provider_id'          => ['nullable', 'string', 'exists:users,id'],
            'provider_name_manual' => ['nullable', 'string', 'max:191'],
            'specialty'            => ['nullable', 'string', 'max:64'],
            'coverage'             => ['nullable', 'string', 'max:191'],

            // Step 3 — notes
            'reason'               => ['required', 'string', 'min:3', 'max:1000'],
            'urgency'              => ['nullable', 'string', 'in:routine,soon,urgent,critical'],
            'notes'                => ['nullable', 'string', 'max:2000'],
            'attachments'          => ['nullable', 'array'],
            'attachments.*'        => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],

            // Step 4 — review
            'hipaa_ack'            => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_name.required'  => 'Please select a client from your roster or enter an identifier.',
            'reason.required'       => 'A reason for the referral is required.',
            'hipaa_ack.accepted'    => 'You must confirm the HIPAA acknowledgement before sending.',
            'provider_slug.required_without' => 'Select a provider from your network or enter a name.',
        ];
    }
}
