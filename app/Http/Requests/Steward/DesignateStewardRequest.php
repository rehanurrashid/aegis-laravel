<?php

declare(strict_types=1);

namespace App\Http\Requests\Steward;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DesignateStewardRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'user_id'              => 'required_without:email|nullable|exists:users,id',
            'email'                => 'required_without:user_id|nullable|email',
            'display_name'         => 'required_without:user_id|nullable|string|max:100',
            'role'                 => ['nullable', Rule::in(['primary', 'alternate', 'secondary'])],
            'steward_category'     => 'nullable|string|max:50',
            'fee_cents'            => 'nullable|integer|min:0',
            'payment_terms'        => ['nullable', Rule::in(['per_incident', 'monthly', 'annual', 'reciprocal'])],
            'auto_charge'          => 'nullable|boolean',
            'expires_days'         => 'nullable|integer|min:1|max:90',
            'message'              => 'nullable|string|max:1000',
            'preselected_user_id'  => 'nullable|exists:users,id',
        ];
    }
}
