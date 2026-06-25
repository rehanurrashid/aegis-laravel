<?php

declare(strict_types=1);

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class ToggleAddOnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'add_on' => ['required', 'string', 'max:64'],
            'enabled' => ['required', 'boolean'],
        ];
    }
}
