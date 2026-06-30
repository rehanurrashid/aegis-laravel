<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDemographicsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'pronouns'             => 'nullable|array',
            'pronouns.*'           => 'string|max:50',
            'ethnicity'            => 'nullable|array',
            'ethnicity.*'          => 'string|max:100',
            'lgbtq_identity'       => 'nullable|array',
            'lgbtq_identity.*'     => 'string|max:100',
            'parenting_status'     => 'nullable|array',
            'parenting_status.*'   => 'string|max:100',
            'religious_orientation'=> 'nullable|array',
            'religious_orientation.*' => 'string|max:100',
            'veteran_status'       => 'nullable|array',
            'veteran_status.*'     => 'string|max:100',
            'supervision_status'   => 'nullable|array',
            'supervision_status.*' => 'string|max:100',
        ];
    }
}
