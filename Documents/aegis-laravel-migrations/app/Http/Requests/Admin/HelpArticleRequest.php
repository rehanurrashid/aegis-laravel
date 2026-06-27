<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class HelpArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['nullable', 'string', 'max:64'],
            'title' => ['required', 'string', 'max:191'],
            'body' => ['required', 'string'],
            'role_visibility' => ['required', 'string', 'in:all,practitioner,continuity_steward,support_steward,business_partner,admin'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'published' => ['nullable', 'boolean'],
        ];
    }
}
