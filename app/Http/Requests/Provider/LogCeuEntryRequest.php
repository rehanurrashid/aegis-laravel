<?php

declare(strict_types=1);

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class LogCeuEntryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'         => ['required', 'string', 'max:191'],
            'provider_name' => ['nullable', 'string', 'max:191'],
            'credit_hours'  => ['required', 'numeric', 'min:0.5', 'max:200'],
            'completed_on'  => ['required', 'date', 'before_or_equal:today'],
            'expires_on'    => ['nullable', 'date', 'after_or_equal:completed_on'],
            'certificate'   => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'credit_hours.min'              => 'Credit hours must be at least 0.5.',
            'completed_on.before_or_equal'  => 'Completion date cannot be in the future.',
            'certificate.max'               => 'Certificate file must be under 10 MB.',
        ];
    }
}
