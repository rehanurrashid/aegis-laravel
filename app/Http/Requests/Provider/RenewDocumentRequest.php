<?php

declare(strict_types=1);

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class RenewDocumentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'effective_date' => 'required|date',
            'expiry_date'    => 'nullable|date|after:effective_date',
            'auto_renew'     => 'nullable|boolean',
            'notes'          => 'nullable|string|max:2000',
        ];
    }
}
