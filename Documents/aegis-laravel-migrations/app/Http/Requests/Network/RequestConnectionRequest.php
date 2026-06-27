<?php

declare(strict_types=1);

namespace App\Http\Requests\Network;

use Illuminate\Foundation\Http\FormRequest;

class RequestConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to_user_id' => ['required', 'string', 'exists:users,id'],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }
}
