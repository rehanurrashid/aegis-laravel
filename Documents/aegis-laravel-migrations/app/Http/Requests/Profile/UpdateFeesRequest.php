<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFeesRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'session_rate_cents'   => 'nullable|integer|min:0',
            'session_length_mins'  => 'nullable|integer|min:15|max:480',
            'accepts_insurance'    => 'nullable|boolean',
            'accepts_cash'         => 'nullable|boolean',
            'insurance_types'      => 'nullable|array',
            'insurance_types.*'    => 'string|max:100',
            'package_available'    => 'nullable|boolean',
            'package_description'  => 'nullable|string|max:500',
            'package_rate_cents'   => 'nullable|integer|min:0',
        ];
    }
}
