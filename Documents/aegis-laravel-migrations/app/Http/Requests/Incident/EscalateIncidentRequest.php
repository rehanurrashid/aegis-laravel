<?php

declare(strict_types=1);

namespace App\Http\Requests\Incident;

use Illuminate\Foundation\Http\FormRequest;

class EscalateIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:1000'],
            'notify_admin' => ['nullable', 'boolean'],
        ];
    }
}
