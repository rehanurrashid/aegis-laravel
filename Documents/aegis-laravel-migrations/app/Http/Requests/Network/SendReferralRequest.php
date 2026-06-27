<?php

declare(strict_types=1);

namespace App\Http\Requests\Network;

use Illuminate\Foundation\Http\FormRequest;

class SendReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'string', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:191'],
            'client_initials' => ['nullable', 'string', 'max:10'],
            'client_age_band' => ['nullable', 'string', 'max:20'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'urgency' => ['nullable', 'string', 'in:low,normal,high,urgent'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
