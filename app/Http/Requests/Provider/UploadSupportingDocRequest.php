<?php

declare(strict_types=1);

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class UploadSupportingDocRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'       => 'required|string|max:200',
            'type'       => 'nullable|string|max:64',
            'related_to' => 'nullable|string|max:100',
            'notes'      => 'nullable|string|max:2000',
            'file'       => 'nullable|file|mimes:pdf,doc,docx,txt|max:10240',
        ];
    }
}
