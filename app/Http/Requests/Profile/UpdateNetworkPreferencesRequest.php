<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNetworkPreferencesRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'network_accepting'   => 'nullable|boolean',
            'network_telehealth'  => 'nullable|boolean',
            'network_insurance'   => 'nullable|array',
            'network_insurance.*' => 'string|max:100',
            'network_languages'   => 'nullable|array',
            'network_languages.*' => 'string|max:50',
            'network_format'      => 'nullable|array',
            'network_format.*'    => 'string|max:50',
        ];
    }
}
