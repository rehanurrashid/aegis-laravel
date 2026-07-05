<?php

declare(strict_types=1);

namespace App\Http\Requests\News;

use Illuminate\Foundation\Http\FormRequest;

class CreateNewsPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'           => ['nullable', 'string', 'max:191'],
            'body'            => ['required', 'string', 'min:1'],
            'post_type'       => ['nullable', 'string', 'max:40'],
            'role_visibility' => ['nullable', 'string', 'max:40'],
            'audience'        => ['nullable', 'string', 'max:40'],
            'pinned'          => ['nullable', 'boolean'],
            'tags'            => ['nullable', 'string', 'max:500'],  // comma-separated, split in service
            'links'           => ['nullable', 'array'],
            'poll_question'   => ['nullable', 'string', 'max:500'],
            'poll_options'    => ['nullable', 'array'],
            'poll_closes_at'  => ['nullable', 'date'],
        ];
    }
}
