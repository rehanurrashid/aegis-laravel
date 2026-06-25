<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrivacyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'practitioner_public'     => 'nullable|boolean',
            'cs_public'               => 'nullable|boolean',
            'business_partner_public' => 'nullable|boolean',
        ];
    }
}
