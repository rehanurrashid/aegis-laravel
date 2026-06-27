<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationGatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notify_email' => ['nullable', 'boolean'],
            'notify_plan' => ['nullable', 'boolean'],
            'notify_incident' => ['nullable', 'boolean'],
            'notify_steward' => ['nullable', 'boolean'],
            'notify_business' => ['nullable', 'boolean'],
            'notify_messages' => ['nullable', 'boolean'],
            'notify_digest' => ['nullable', 'boolean'],
            'digest_cadence' => ['nullable', 'string', 'in:weekly,monthly,off'],
        ];
    }
}
