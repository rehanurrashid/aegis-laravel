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
        $role   = $this->input('role', '');
        $csPath = $this->input('cs_path', '');

        return [
            // ── Core identity (all roles) ──────────────────────────────────────
            'display_name' => ['required', 'string', 'max:100'],
            'email'        => ['required', 'email', 'unique:users,email'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
            'phone'        => ['nullable', 'string', 'max:20'],

            // ── Role ──────────────────────────────────────────────────────────
            'role' => [
                'required',
                Rule::in(['practitioner', 'continuity_steward', 'support_steward', 'business_partner']),
            ],

            // ── BP type (agency / freelancer) ─────────────────────────────────
            'bp_type' => [
                Rule::requiredIf($role === 'business_partner'),
                'nullable',
                Rule::in(['freelancer', 'agency']),
            ],

            // ── CS path (business / invited) ──────────────────────────────────
            'cs_path' => [
                Rule::requiredIf($role === 'continuity_steward'),
                'nullable',
                Rule::in(['business', 'invited']),
            ],

            // ── Invitation code (invited CS only) ─────────────────────────────
            'invitation_code' => array_filter([
                Rule::requiredIf($role === 'continuity_steward' && $csPath === 'invited'),
                'nullable',
                'string',
                'max:64',
                // Validate code exists and is still valid
                ($role === 'continuity_steward' && $csPath === 'invited')
                    ? \Illuminate\Validation\Rule::exists('plan_stewards', 'id')
                        ->whereIn('status', ['invited', 'pending'])
                        ->where('expires_at', '>=', now())
                    : null,
            ]),

            // ── Practitioner tier selection (shown at plan step post-verify) ───
            // Also accepted at registration for demo/fast-path flows.
            'tier' => [
                'nullable',
                Rule::in(['access', 'practice']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'display_name.required'   => 'Your full name is required.',
            'email.required'          => 'Email address is required.',
            'email.unique'            => 'An account with this email already exists.',
            'password.min'            => 'Password must be at least 8 characters.',
            'role.required'           => 'Please select your role to continue.',
            'role.in'                 => 'Selected role is not valid.',
            'bp_type.required'        => 'Please select your business type (Freelancer or Agency).',
            'cs_path.required'        => 'Please select your Continuity Steward pathway.',
            'invitation_code.required'=> 'An invitation code is required for invited Continuity Stewards.',
            'invitation_code.exists'  => 'This invitation code is invalid or has expired. Please ask your practitioner to resend your invitation.',
        ];
    }
}
