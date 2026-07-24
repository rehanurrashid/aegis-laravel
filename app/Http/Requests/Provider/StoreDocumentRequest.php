<?php

declare(strict_types=1);

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $isDraft = $this->boolean('is_draft');

        return [
            // Required when sending for signature (not a draft)
            'category'       => ($isDraft ? 'nullable' : 'required') . '|string|max:32',
            'doc_type'       => ($isDraft ? 'nullable' : 'required') . '|string|max:64',
            'party_b_id'     => ($isDraft ? 'nullable' : 'required') . '|string|max:36',
            'party_c_id'     => 'nullable|string|max:36',
            'effective_date' => ($isDraft ? 'nullable' : 'required') . '|date',
            // Always optional
            'reference'      => 'nullable|string|max:64',
            'title'          => 'nullable|string|max:200',
            'expiry_date'    => 'nullable|date|after_or_equal:effective_date',
            'auto_renew'     => 'nullable|string',
            'notes'          => 'nullable|string|max:5000',
            'is_draft'       => 'nullable|boolean',
            // Amendment fields
            'parent_id'      => 'nullable|string|max:36',
            'type'           => 'nullable|string|max:100',
            'proposed'       => 'nullable|string|max:10000',
            'reason'         => 'nullable|string|max:2000',
            // Email/sig options
            'my_action'      => 'nullable|string',
            'notify_method'  => 'nullable|string',
            'deadline'       => 'nullable|date',
            'message'        => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'category.required'       => 'Please select an agreement category.',
            'doc_type.required'       => 'Document type is required.',
            'party_b_id.required'     => 'A counterparty must be selected.',
            'effective_date.required' => 'Effective date is required.',
            'expiry_date.after_or_equal' => 'Expiry date must be on or after the effective date.',
        ];
    }
}
