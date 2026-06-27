<?php

declare(strict_types=1);

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class ConfigureIncidentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'string', 'exists:continuity_plans,id'],
            'configs' => ['required', 'array'],
            'configs.*.incident_type' => ['required', 'string'],
            'configs.*.enabled' => ['required', 'boolean'],
            'configs.*.notification_window_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
        ];
    }
}
