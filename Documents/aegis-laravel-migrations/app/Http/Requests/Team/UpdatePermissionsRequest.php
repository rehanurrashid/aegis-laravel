<?php

declare(strict_types=1);

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permission_role' => ['required', 'string', 'in:admin,manager,specialist,viewer'],
            'department' => ['nullable', 'string', 'max:64'],
        ];
    }
}
