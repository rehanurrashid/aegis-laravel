<?php

declare(strict_types=1);

namespace App\Http\Requests\Messages;

use Illuminate\Foundation\Http\FormRequest;

class CreateThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['string', 'exists:users,id'],
            'subject' => ['nullable', 'string', 'max:191'],
            'initial_message' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
