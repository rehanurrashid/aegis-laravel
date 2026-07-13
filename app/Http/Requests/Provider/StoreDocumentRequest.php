<?php

declare(strict_types=1);

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // Wizard fields
            'category'       => 'nullable|string|max:32',
            'doc_type'       => 'nullable|string|max:64',
            'reference'      => 'nullable|string|max:64',
            'title'          => 'nullable|string|max:200',
            'party_b_id'     => 'nullable|string|max:36',
            'effective_date' => 'nullable|date',
            'expiry_date'    => 'nullable|date',
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
}
