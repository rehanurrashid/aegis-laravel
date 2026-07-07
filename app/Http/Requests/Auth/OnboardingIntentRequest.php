<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class OnboardingIntentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'intent'      => ['required', 'string', 'in:provider,continuity_steward,support_steward,business_partner'],
            'use_cases'   => ['nullable', 'array'],
            'use_cases.*' => ['string', 'max:100'],
        ];
    }
}
