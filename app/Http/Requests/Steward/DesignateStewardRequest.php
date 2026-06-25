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
            'user_id'      => 'required_without:email|nullable|exists:users,id',
            'email'        => 'required_without:user_id|nullable|email',
            'display_name' => 'required_without:user_id|nullable|string|max:100',
            'role'         => ['nullable', Rule::in(['primary', 'alternate', 'secondary'])],
            'steward_category' => 'nullable|string|max:50',
        ];
    }
}
