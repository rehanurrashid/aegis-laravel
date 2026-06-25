<?php

declare(strict_types=1);

namespace App\Http\Requests\Services;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:64'],
            'price_cents' => ['nullable', 'integer', 'min:0'],
            'price_type' => ['nullable', 'string', 'in:fixed,hourly,session,inquiry'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'is_public' => ['nullable', 'boolean'],
        ];
    }
}
