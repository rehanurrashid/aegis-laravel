<?php

declare(strict_types=1);

namespace App\Http\Requests\Docs;

use Illuminate\Foundation\Http\FormRequest;

class SendForSignatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_id' => ['required', 'string', 'exists:continuity_documents,id'],
            'signer_ids' => ['required', 'array', 'min:1'],
            'signer_ids.*' => ['string', 'exists:users,id'],
        ];
    }
}
