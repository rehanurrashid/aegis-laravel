<?php

declare(strict_types=1);

namespace App\Http\Requests\Incident;

use Illuminate\Foundation\Http\FormRequest;

class ReportIncidentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'plan_id'           => 'required|exists:continuity_plans,id',
            'incident_type'     => 'required|string|max:50',
            'report_narrative'  => 'required|string|min:10|max:2000',
            'documentation'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'contact_attempts'  => 'nullable|array',
        ];
    }
}
