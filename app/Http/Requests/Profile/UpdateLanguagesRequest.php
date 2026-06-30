<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLanguagesRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'languages'   => 'required|array',
            'languages.*' => 'string|max:50',
            'website'     => 'nullable|string|max:255',
        ];
    }
}
