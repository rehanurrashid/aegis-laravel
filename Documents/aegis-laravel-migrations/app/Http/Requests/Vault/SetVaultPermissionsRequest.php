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
            'access_level'  => ['nullable', Rule::in(['cs_only', 'ss_only', 'both', 'practitioner_only'])],
            'steward_ids'   => 'nullable|array',
            'steward_ids.*' => 'exists:users,id',
            'release_on_incident' => 'nullable|boolean',
        ];
    }
}
