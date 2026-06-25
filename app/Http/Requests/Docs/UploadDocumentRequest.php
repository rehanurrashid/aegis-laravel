<?php

declare(strict_types=1);

namespace App\Http\Requests\Docs;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:191'],
            'document_type' => ['required', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:1000'],
            'document' => ['required', 'file', 'max:20480'],
        ];
    }
}
