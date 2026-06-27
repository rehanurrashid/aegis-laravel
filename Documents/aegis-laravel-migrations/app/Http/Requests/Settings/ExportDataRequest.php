<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class ExportDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'format' => ['required', 'string', 'in:json,csv,zip'],
            'include' => ['nullable', 'array'],
            'include.*' => ['string', 'in:plans,vault,incidents,messages,activity,payments'],
        ];
    }
}
