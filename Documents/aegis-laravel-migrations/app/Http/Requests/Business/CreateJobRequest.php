<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateJobRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'             => 'required|string|max:200',
            'description'       => 'required|string|min:20|max:5000',
            'category'          => 'required|string|max:100',
            'budget_min_cents'  => 'nullable|integer|min:0',
            'budget_max_cents'  => 'nullable|integer|min:0|gte:budget_min_cents',
            'budget_type'       => ['required', Rule::in(['fixed', 'hourly', 'retainer'])],
            'location_type'     => ['nullable', Rule::in(['remote', 'onsite', 'hybrid'])],
            'expires_at'        => 'nullable|date|after:today',
        ];
    }
}
