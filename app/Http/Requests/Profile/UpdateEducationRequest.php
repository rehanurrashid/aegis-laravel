<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEducationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'education'              => 'required|array',
            'education.*.degree'     => 'nullable|string|max:191',
            'education.*.field'      => 'nullable|string|max:191',
            'education.*.institution'=> 'nullable|string|max:191',
            'education.*.duration'   => 'nullable|string|max:100',
        ];
    }
}
