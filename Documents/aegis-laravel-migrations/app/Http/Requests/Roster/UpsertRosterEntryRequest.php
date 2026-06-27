<?php

declare(strict_types=1);

namespace App\Http\Requests\Roster;

use Illuminate\Foundation\Http\FormRequest;

class UpsertRosterEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:ceu,credential,experience'],
            'title' => ['required', 'string', 'max:191'],
            'provider_name' => ['nullable', 'string', 'max:191'],
            'credit_hours' => ['nullable', 'numeric', 'min:0'],
            'completed_on' => ['nullable', 'date'],
            'expires_on' => ['nullable', 'date', 'after_or_equal:completed_on'],
            'certificate_ref' => ['nullable', 'string', 'max:255'],
        ];
    }
}
