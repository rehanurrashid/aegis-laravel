<?php

declare(strict_types=1);

namespace App\Http\Requests\Steward;

use Illuminate\Foundation\Http\FormRequest;

class CertifyPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'string', 'exists:continuity_plans,id'],
            'attestation_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
