<?php

declare(strict_types=1);

namespace App\Http\Requests\Network;

use Illuminate\Foundation\Http\FormRequest;

class AddShadowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'string', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
