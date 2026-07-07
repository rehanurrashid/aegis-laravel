<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class OnboardingSubscribeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'payment_method_id' => ['required', 'string', 'starts_with:pm_'],
            'price_id'          => ['required', 'string', 'starts_with:price_'],
            'addons'            => ['nullable', 'array'],
            'addons.*'          => ['string', 'in:maat'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method_id.required'    => 'Payment information is required.',
            'payment_method_id.starts_with' => 'Invalid payment method. Please try again.',
            'price_id.required'             => 'Please select a plan.',
            'price_id.starts_with'          => 'Invalid plan selection. Please go back and choose a plan.',
        ];
    }
}
