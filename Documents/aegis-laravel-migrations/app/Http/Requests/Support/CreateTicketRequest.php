<?php

declare(strict_types=1);

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTicketRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'subject'  => 'required|string|max:200',
            'body'     => 'required|string|min:10|max:5000',
            'category' => ['nullable', Rule::in(['support_ticket', 'feedback', 'bug', 'feature_request', 'billing', 'other'])],
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high'])],
            'channel'  => ['nullable', Rule::in(['ticket', 'in_app', 'email'])],
        ];
    }
}
