<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitProposalRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'cover_letter'         => 'required|string|min:50|max:3000',
            'proposed_rate_cents'  => 'required|integer|min:0',
            'rate_type'            => ['nullable', Rule::in(['fixed', 'hourly', 'retainer'])],
            'timeline_days'        => 'nullable|integer|min:1|max:730',
            'portfolio_url'        => 'nullable|url|max:500',
        ];
    }
}
