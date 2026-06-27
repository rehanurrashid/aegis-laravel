<?php

declare(strict_types=1);

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class AnnualReviewRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'checklist'              => 'required|array',
            'checklist.stewards'     => 'required|accepted',
            'checklist.incidents'    => 'required|accepted',
            'checklist.documents'    => 'required|accepted',
            'checklist.vault'        => 'required|accepted',
            'checklist.tasks'        => 'required|accepted',
            'checklist.fees'         => 'required|accepted',
            'checklist.contacts'     => 'required|accepted',
            'checklist.preferences'  => 'required|accepted',
            'notes'                  => 'nullable|string|max:2000',
        ];
    }
}
