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
            'title' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:64'],
            'price_cents' => ['nullable', 'integer', 'min:0'],
            'price_type' => ['required', 'string', 'in:fixed,hourly,session,inquiry'],
            'is_public' => ['nullable', 'boolean'],
        ];
    }
}
