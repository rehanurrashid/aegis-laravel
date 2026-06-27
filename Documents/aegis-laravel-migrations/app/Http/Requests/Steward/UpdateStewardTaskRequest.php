<?php

declare(strict_types=1);

namespace App\Http\Requests\Steward;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStewardTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:pending,in_progress,completed,blocked'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'completed_at' => ['nullable', 'date'],
        ];
    }
}
