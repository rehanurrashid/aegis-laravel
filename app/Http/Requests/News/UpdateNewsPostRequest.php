<?php

declare(strict_types=1);

namespace App\Http\Requests\News;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNewsPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:191'],
            'body' => ['nullable', 'string'],
            'post_type' => ['nullable', 'string', 'in:post,poll,announcement'],
            'role_visibility' => ['nullable', 'string', 'max:40'],
            'pinned' => ['nullable', 'boolean'],
        ];
    }
}
