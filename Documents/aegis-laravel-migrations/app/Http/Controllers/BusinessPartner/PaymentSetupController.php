<?php

declare(strict_types=1);

namespace App\Http\Controllers\BusinessPartner;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentSetupController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        return Inertia::render('BusinessPartner/PaymentSetup', [
            'stripeConnectStatus' => $user->stripe_connect_account_id ? 'connected' : 'disconnected',
            'accountDetails'      => ['account_id' => $user->stripe_connect_account_id],
        ]);
    }

    public function connect(Request $request): RedirectResponse
    {
        $data = $request->validate(['account_id' => 'required|string|max:100']);
        $request->user()->update(['stripe_connect_account_id' => $data['account_id']]);
        return back()->with('success', 'Stripe Connect linked.');
    }
}
