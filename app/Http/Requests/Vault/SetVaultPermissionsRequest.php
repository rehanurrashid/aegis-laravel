<?php

declare(strict_types=1);

namespace App\Http\Requests\Vault;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetVaultPermissionsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'steward_ids'         => 'nullable|array',
            'steward_ids.*'       => 'exists:users,id',
            'vault_access'        => ['nullable', Rule::in(['none', 'metadata', 'scoped', 'full'])],
            'release_on_incident' => 'nullable|boolean',
        ];
    }
}
