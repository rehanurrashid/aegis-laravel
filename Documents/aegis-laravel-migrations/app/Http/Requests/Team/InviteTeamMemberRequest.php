<?php

declare(strict_types=1);

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;

class InviteTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:191'],
            'permission_role' => ['required', 'string', 'in:admin,manager,specialist,viewer'],
            'department' => ['nullable', 'string', 'max:64'],
        ];
    }
}
