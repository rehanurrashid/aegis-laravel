<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tier' => ['required', 'string', 'max:40'],
            'price_monthly_cents' => ['nullable', 'integer', 'min:0'],
            'price_annual_cents' => ['nullable', 'integer', 'min:0'],
            'feature_flags' => ['nullable', 'array'],
            'limits' => ['nullable', 'array'],
        ];
    }
}
