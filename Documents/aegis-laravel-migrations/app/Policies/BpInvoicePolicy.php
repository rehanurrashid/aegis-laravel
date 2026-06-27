<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BpContract;
use App\Models\BpInvoice;
use App\Models\User;

class BpInvoicePolicy
{
    public function create(User $user, BpContract $contract): bool
    {
        return $user->id === $contract->bp_id;
    }

    public function pay(User $user, BpInvoice $invoice): bool
    {
        return $user->id === $invoice->practitioner_id;
    }

    public function void(User $user, BpInvoice $invoice): bool
    {
        return $user->id === $invoice->bp_id
            && in_array((string) $invoice->status, ['draft', 'sent'], true);
    }
}
