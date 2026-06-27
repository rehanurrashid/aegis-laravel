<?php

declare(strict_types=1);

namespace App\Http\Requests\Incident;

use Illuminate\Foundation\Http\FormRequest;

class VerifyIncidentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'notes'         => 'required|string|min:10|max:2000',
            'docs'          => 'nullable|array',
            'documentation' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ];
    }
}
