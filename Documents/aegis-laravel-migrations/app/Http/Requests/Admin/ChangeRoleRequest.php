<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeRoleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'role'   => ['required', Rule::in(['practitioner', 'continuity_steward', 'support_steward', 'business_partner', 'admin'])],
            'reason' => 'required|string|min:10|max:500',
        ];
    }
}
