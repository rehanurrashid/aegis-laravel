<?php

declare(strict_types=1);

namespace App\Http\Requests\Docs;

use Illuminate\Foundation\Http\FormRequest;

class SignReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'signer_id' => ['required', 'string', 'exists:users,id'],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }
}
