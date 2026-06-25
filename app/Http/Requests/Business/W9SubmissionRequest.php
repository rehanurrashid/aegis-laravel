<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

class W9SubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'legal_name' => ['required', 'string', 'max:191'],
            'business_name' => ['nullable', 'string', 'max:191'],
            'tax_classification' => ['required', 'string', 'max:64'],
            'tin' => ['required', 'string', 'max:32'],
            'document' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
