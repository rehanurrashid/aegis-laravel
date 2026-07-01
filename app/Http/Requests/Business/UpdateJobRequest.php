<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'                 => 'required|string|max:191',
            'category'              => 'required|string|max:64',
            'job_type'               => ['nullable', Rule::in(['one_time', 'ongoing', 'contract', 'part_time', 'full_time'])],
            'location_pref'          => ['nullable', Rule::in(['remote', 'onsite', 'hybrid'])],
            'start_date'             => 'nullable|date',
            'tags'                    => 'nullable|array',
            'tags.*'                  => 'string|max:40',
            'description'            => 'required|string|min:20|max:5000',

            'experience_level'       => ['nullable', Rule::in(['entry', 'mid', 'senior', 'expert'])],
            'partner_type_pref'      => ['nullable', Rule::in(['freelancer', 'agency', 'consultant', 'firm', 'solopreneur'])],
            'certifications'         => 'nullable|string|max:1000',
            'requires_hipaa'         => 'boolean',
            'requires_nda'           => 'boolean',
            'requires_baa'           => 'boolean',
            'application_deadline'   => 'nullable|date',
            'max_applicants'         => 'nullable|integer|min:0',

            'budget_type'            => ['required', Rule::in(['fixed', 'hourly', 'retainer'])],
            'budget_amount_cents'    => 'nullable|integer|min:0',
            'payment_method'         => 'nullable|string|max:40',
            'billing_frequency'      => 'nullable|string|max:40',
            'perks'                  => 'nullable|string|max:255',
            'is_featured'            => 'boolean',
            'is_urgent'              => 'boolean',

            'internal_notes'         => 'nullable|string|max:2000',
            'status'                 => ['required', Rule::in(['draft', 'open', 'paused', 'closed', 'filled', 'cancelled'])],
        ];
    }
}
