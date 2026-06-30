<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProposalStageRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'pipeline_stage'   => ['required', Rule::in(['new', 'reviewed', 'shortlisted', 'interview'])],
            'note'             => 'nullable|string|max:2000',
            'interview_type'   => 'nullable|string|max:20',
            'interview_at'     => 'nullable|date',
            'notify_applicant' => 'boolean',
        ];
    }
}
