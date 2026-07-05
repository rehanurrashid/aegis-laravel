<?php

declare(strict_types=1);

namespace App\Http\Requests\News;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNewsPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only the post author may update
        $post = $this->route('post');
        return $post && $post->author_id === $this->user()?->id;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:191'],
            'body'  => ['required', 'string', 'min:1'],
        ];
    }
}
