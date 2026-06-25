<?php

declare(strict_types=1);

namespace App\Http\Requests\Incident;

use Illuminate\Foundation\Http\FormRequest;

class IncidentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'update_type' => ['required', 'string', 'max:32'],
            'body' => ['required', 'string', 'min:5', 'max:5000'],
            'visibility' => ['nullable', 'string', 'in:public,internal'],
        ];
    }
}
