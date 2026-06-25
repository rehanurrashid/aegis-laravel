<?php

declare(strict_types=1);

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class ChangeTierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tier' => ['required', 'string', 'in:essentials,plus,practice'],
            'billing_cycle' => ['required', 'string', 'in:monthly,annual'],
        ];
    }
}
