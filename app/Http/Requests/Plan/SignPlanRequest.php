<?php

declare(strict_types=1);

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class SignPlanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'confirmed' => 'required|accepted',
            'title'     => 'nullable|string|max:100',
        ];
    }
}
