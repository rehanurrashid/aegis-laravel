<?php

declare(strict_types=1);

namespace App\Http\Controllers\BusinessPartner;

use App\Http\Controllers\Concerns\CreatesSetupIntent;
use App\Http\Controllers\Controller;

class PaymentMethodSetupController extends Controller
{
    use CreatesSetupIntent;
}
