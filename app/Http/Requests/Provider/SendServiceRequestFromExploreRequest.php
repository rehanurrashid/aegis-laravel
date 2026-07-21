<?php

declare(strict_types=1);

namespace App\Http\Requests\Provider;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Wave 3 — Send a service request from the Explore tab.
 *
 * Different from the public profile ServiceRequest route:
 *  - Requires an authenticated provider (not public)
 *  - service_id must be an active, public service
 *  - Practitioner cannot request their own service
 */
class SendServiceRequestFromExploreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $serviceId = $this->input('service_id');
        if (!$serviceId) return false;

        $service = Service::find($serviceId);
        if (!$service) return false;

        // Cannot request own service
        if ($service->practitioner_id === $this->user()->id) return false;

        // Service must be active and public
        $status = $service->status instanceof \App\Enums\ServiceStatus
            ? $service->status->value
            : (string) $service->status;

        return $status === 'active' && (bool) $service->is_public;
    }

    public function rules(): array
    {
        return [
            'service_id'                   => 'required|string|exists:services,id',
            'message'                      => 'nullable|string|max:2000',
            'preferred_date'               => 'nullable|date|after_or_equal:today',
            'preferred_time'               => 'nullable|string|max:10',
            'preferred_timezone'           => 'nullable|string|max:64',
            'format'                       => 'nullable|string|in:telehealth,in_person,both',
            // Rev 4 — proposed payment terms
            'proposed_payment_structure'   => 'required|in:full_upfront,split,full_on_completion',
            'proposed_upfront_percentage'  => 'required_if:proposed_payment_structure,split|nullable|integer|min:1|max:99',
            'proposed_terms_note'          => 'nullable|string|max:2000',
            'terms_source'                 => 'required|in:provider_default,client_proposed',
            'agree_terms'                  => 'required|accepted',
        ];
    }

    public function messages(): array
    {
        return [
            'service_id.required' => 'A service must be selected.',
            'service_id.exists'   => 'The selected service no longer exists.',
            'preferred_date.after_or_equal' => 'Preferred date must be today or in the future.',
        ];
    }
}
