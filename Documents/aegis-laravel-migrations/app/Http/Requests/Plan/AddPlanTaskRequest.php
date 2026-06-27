<?php

declare(strict_types=1);

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddPlanTaskRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'         => 'required|string|max:200',
            'description'   => 'nullable|string|max:2000',
            'incident_type' => 'required|string|max:50',
            'assigned_to'   => ['required', Rule::in(['continuity_steward', 'support_steward'])],
            'category'      => 'nullable|string|max:50',
            'timeline'      => 'nullable|string|max:100',
            'sort_order'    => 'nullable|integer|min:0',
            'is_custom'     => 'nullable|boolean',
        ];
    }
}
