<?php

declare(strict_types=1);

namespace App\Http\Requests\News;

use Illuminate\Foundation\Http\FormRequest;

class SubmitEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:191'],
            'type'        => ['required', 'string', 'in:webinar,conference,training,networking,workshop'],
            'date'        => ['required', 'date', 'after:today'],
            'description' => ['required', 'string', 'max:2000'],
            'location'    => ['nullable', 'string', 'max:191'],
            'price_cents' => ['nullable', 'integer', 'min:0'],
            'ceu'         => ['nullable', 'numeric', 'min:0', 'max:99'],
            'url'         => ['nullable', 'url', 'max:500'],
            'organizer'   => ['nullable', 'string', 'max:191'],
        ];
    }
}
