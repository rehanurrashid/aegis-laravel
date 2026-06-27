<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCredentialsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'credentials'              => 'required|array',
            'credentials.*.type'       => 'required|string|max:50',
            'credentials.*.label'      => 'required|string|max:100',
            'credentials.*.number'     => 'nullable|string|max:100',
            'credentials.*.issued_at'  => 'nullable|date',
            'credentials.*.expires_at' => 'nullable|date|after:today',
            'verified'                 => 'nullable|boolean',
        ];
    }
}
