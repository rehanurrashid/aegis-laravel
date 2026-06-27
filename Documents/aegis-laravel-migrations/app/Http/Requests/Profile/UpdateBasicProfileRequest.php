<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBasicProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'display_name'    => 'nullable|string|max:100',
            'bio'             => 'nullable|string|max:500',
            'about_me'        => 'nullable|string|max:2000',
            'phone'           => 'nullable|string|max:20',
            'title'           => 'nullable|string|max:100',
            'organization'    => 'nullable|string|max:200',
            'location'        => 'nullable|string|max:200',
            'avatar_initials' => 'nullable|string|max:5',
        ];
    }
}
