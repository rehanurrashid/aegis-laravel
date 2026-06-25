<?php

declare(strict_types=1);

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

class CreateInvoiceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'contract_id'                   => 'required|exists:bp_contracts,id',
            'invoice_number'                => 'nullable|string|max:50',
            'due_at'                        => 'required|date|after:today',
            'tax_cents'                     => 'nullable|integer|min:0',
            'line_items'                    => 'required|array|min:1',
            'line_items.*.description'      => 'required|string|max:500',
            'line_items.*.quantity'         => 'required|integer|min:1',
            'line_items.*.unit_price_cents' => 'required|integer|min:0',
            'notes'                         => 'nullable|string|max:2000',
        ];
    }
}
