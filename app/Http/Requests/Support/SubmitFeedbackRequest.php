<?php

declare(strict_types=1);

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitFeedbackRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'body'     => 'required|string|min:5|max:2000',
            'channel'  => ['nullable', Rule::in(['in_app', 'email', 'fab'])],
            'rating'   => 'nullable|integer|between:1,5',
            'category' => ['nullable', Rule::in(['feedback', 'bug', 'feature_request', 'other'])],
        ];
    }
}
