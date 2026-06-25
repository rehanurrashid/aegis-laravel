<?php

declare(strict_types=1);

namespace App\Http\Requests\Docs;

use Illuminate\Foundation\Http\FormRequest;

class AmendDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'changes' => ['required', 'string'],
        ];
    }
}
