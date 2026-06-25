<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount_cents' => ['nullable', 'integer', 'min:0'],
            'cover_letter' => ['nullable', 'string', 'max:5000'],
            'proposed_start_at' => ['nullable', 'date'],
            'proposed_end_at' => ['nullable', 'date', 'after_or_equal:proposed_start_at'],
        ];
    }
}
