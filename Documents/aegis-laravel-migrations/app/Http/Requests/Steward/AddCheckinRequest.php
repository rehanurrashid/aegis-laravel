<?php

declare(strict_types=1);

namespace App\Http\Requests\Steward;

use Illuminate\Foundation\Http\FormRequest;

class AddCheckinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'practitioner_id' => ['required', 'string', 'exists:users,id'],
            'status' => ['required', 'string', 'in:contact,wellness,no_response,emergency'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'next_checkin_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
