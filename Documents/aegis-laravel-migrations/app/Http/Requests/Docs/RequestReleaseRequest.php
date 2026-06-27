<?php

declare(strict_types=1);

namespace App\Http\Requests\Docs;

use Illuminate\Foundation\Http\FormRequest;

class RequestReleaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_id' => ['required', 'string', 'exists:continuity_documents,id'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }
}
