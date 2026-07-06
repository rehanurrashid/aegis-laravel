<?php

declare(strict_types=1);

namespace App\Http\Requests\News;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNewsPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $post = $this->route('post');
        return $post && $post->author_id === $this->user()?->id;
    }

    public function rules(): array
    {
        return [
            'title'           => ['nullable', 'string', 'max:191'],
            'body'            => ['nullable', 'string'],
            'tags'            => ['nullable', 'string', 'max:500'],
            'links'           => ['nullable', 'array'],
            'poll_question'   => ['nullable', 'string', 'max:500'],
            'poll_options'    => ['nullable', 'array'],
            'poll_closes_at'  => ['nullable', 'date'],
            'resource_url'    => ['nullable', 'url', 'max:500'],
        ];
    }
}
