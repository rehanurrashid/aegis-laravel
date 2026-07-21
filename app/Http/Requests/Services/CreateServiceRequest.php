<?php

declare(strict_types=1);

namespace App\Http\Requests\Services;

use Illuminate\Foundation\Http\FormRequest;

class CreateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'                      => ['required', 'string', 'max:191'],
            'description'                => ['nullable', 'string', 'max:5000'],
            'category'                   => ['nullable', 'string', 'max:64'],
            'price_cents'                => ['nullable', 'integer', 'min:0'],
            'price_type'                 => ['required', 'string', 'in:fixed,hourly,session,inquiry'],
            'duration_min'               => ['nullable', 'integer', 'min:1'],
            'format'                     => ['nullable', 'string', 'in:telehealth,in_person,both'],
            'is_public'                  => ['nullable', 'boolean'],
            // Rev 4 — default payment terms
            'default_payment_structure'  => ['nullable', 'in:full_upfront,split,full_on_completion'],
            'default_upfront_percentage' => ['nullable', 'integer', 'min:1', 'max:99'],
            'default_terms_note'         => ['nullable', 'string', 'max:2000'],
            'allow_completion_only'      => ['nullable', 'boolean'],
        ];
    }
}
