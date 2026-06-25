<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'display_name' => 'required|string|max:100',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|string|min:8|confirmed',
            'role'         => ['required', Rule::in(['practitioner', 'continuity_steward', 'support_steward', 'business_partner'])],
            'phone'        => 'nullable|string|max:20',
            'tier'         => ['nullable', Rule::in(['access', 'practice'])],
            'bp_type'      => ['required_if:role,business_partner', Rule::in(['freelancer', 'agency'])],
        ];
    }
}
