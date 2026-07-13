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
            'zone'                => ['required', Rule::in(['standard', 'emergency', 'credentials', 'roster'])],
            'title'               => 'required|string|max:200',
            'description'         => 'nullable|string|max:1000',
            'category'            => 'nullable|string|max:100',
            'file'                => 'nullable|file|max:51200', // 50MB; nullable so credentials/roster entries without file still validate
            'issued_at'           => 'nullable|date',
            'expires_at'          => 'nullable|date',
            // Credential zone fields
            'credential_username' => 'nullable|string|max:191',
            'credential_password' => 'nullable|string|max:500',
            'credential_url'      => 'nullable|url|max:255',
            // Roster zone fields
            'client_name'         => 'nullable|string|max:191',
            'client_location'     => 'nullable|string|max:191',
            'client_phone'        => 'nullable|string|max:30',
            'client_service'      => 'nullable|string|max:191',
            'client_priority'     => 'nullable|boolean',
        ];
    }
}
