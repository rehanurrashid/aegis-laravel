<?php

declare(strict_types=1);

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class TerminateDocumentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'reason'       => 'required|string|max:100',
            'term_date'    => 'nullable|date',
            'notes'        => 'nullable|string|max:2000',
            'confirm'      => 'required|string',
        ];
    }
}
