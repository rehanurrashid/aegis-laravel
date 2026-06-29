<?php

declare(strict_types=1);

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class StoreProviderCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cred_type'   => ['required', 'string', 'max:64'],
            'custom_type' => ['nullable', 'string', 'max:64'],
            'name'        => ['nullable', 'string', 'max:191'],
            'subtitle'    => ['nullable', 'string', 'max:191'],
            'issuer'      => ['nullable', 'string', 'max:191'],
            'number'      => ['nullable', 'string', 'max:191'],
            'issued_on'   => ['nullable', 'date'],
            'expires_on'  => ['nullable', 'date', 'after_or_equal:issued_on'],
            'document'    => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ];
    }
}
