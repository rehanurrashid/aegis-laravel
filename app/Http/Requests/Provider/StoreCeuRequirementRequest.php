<?php

declare(strict_types=1);

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class StoreCeuRequirementRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'jurisdiction'   => ['required', 'string', 'max:191'],
            'total_hours'    => ['required', 'integer', 'min:1', 'max:200'],
            'cycle'          => ['required', 'string', 'in:annual,biannual'],
            'due_date'       => ['nullable', 'date'],
            'required_types' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
