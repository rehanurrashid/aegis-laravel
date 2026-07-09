<?php

declare(strict_types=1);

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Concerns\CreatesSetupIntent;
use App\Http\Controllers\Controller;

/**
 * Provider "Add card" native modal — returns Stripe SetupIntent client_secret.
 * Called by AddCardModal.vue before rendering Stripe Elements.
 */
class PaymentMethodSetupController extends Controller
{
    use CreatesSetupIntent;
}
