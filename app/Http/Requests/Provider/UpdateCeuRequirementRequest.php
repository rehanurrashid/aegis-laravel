<?php

declare(strict_types=1);

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCeuRequirementRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'jurisdiction'   => ['sometimes', 'required', 'string', 'max:191'],
            'total_hours'    => ['sometimes', 'required', 'integer', 'min:1', 'max:200'],
            'cycle'          => ['sometimes', 'required', 'string', 'in:annual,biannual'],
            'due_date'       => ['nullable', 'date'],
            'required_types' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
