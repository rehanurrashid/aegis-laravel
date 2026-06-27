<?php

declare(strict_types=1);

namespace App\Http\Requests\Steward;

use Illuminate\Foundation\Http\FormRequest;

class ResignStewardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:1000'],
            'effective_at' => ['nullable', 'date'],
        ];
    }
}
