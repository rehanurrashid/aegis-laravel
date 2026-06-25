<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvailabilityRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'hours'      => 'nullable|array',
            'accepting'  => 'nullable|boolean',
            'telehealth' => 'nullable|boolean',
        ];
    }
}
