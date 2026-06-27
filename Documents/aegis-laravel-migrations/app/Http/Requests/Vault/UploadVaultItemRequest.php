<?php

declare(strict_types=1);

namespace App\Http\Requests\Vault;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadVaultItemRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'zone'        => ['required', Rule::in(['standard', 'emergency', 'credentials', 'roster'])],
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string|max:500',
            'file'        => 'required|file|max:51200', // 50MB
        ];
    }
}
