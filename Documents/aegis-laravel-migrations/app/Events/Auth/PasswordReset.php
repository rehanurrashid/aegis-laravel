<?php

declare(strict_types=1);

namespace App\Events\Auth;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;


class PasswordReset
{
    use Dispatchable, SerializesModels;

    public function __construct(public \App\Models\User $user) {}

}
