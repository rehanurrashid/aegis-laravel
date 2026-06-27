<?php

declare(strict_types=1);

namespace App\Http\Requests\Incident;

use Illuminate\Foundation\Http\FormRequest;

class AttachIncidentDocRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'incident_id' => ['required', 'string', 'exists:critical_incidents,id'],
            'document_id' => ['nullable', 'string', 'exists:continuity_documents,id'],
            'document' => ['nullable', 'file', 'max:20480'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
