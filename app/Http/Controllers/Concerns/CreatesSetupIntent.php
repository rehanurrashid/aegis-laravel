<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Reusable "create a Stripe SetupIntent for the current user" endpoint.
 *
 * Used by native in-app "Add card" modal (Rev 2 §0.11) — Provider, CS, BP
 * all mount the same AddCardModal Vue component, which POSTs here first
 * to get a client_secret, then confirms the card with Stripe Elements,
 * then POSTs the PaymentMethod id to the appropriate storePaymentMethod
 * route.
 *
 * The user must already exist as a Stripe customer. If not, we create
 * them via Cashier's createOrGetStripeCustomer() before creating the
 * SetupIntent.
 */
trait CreatesSetupIntent
{
    public function createSetupIntent(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            if (!$user->hasStripeId()) {
                $user->createAsStripeCustomer([
                    'name'  => $user->display_name,
                    'email' => $user->email,
                ]);
            }

            // 'card' + 'off_session' is the canonical pattern for saving cards for
            // future use (subscriptions, peer payments). This ensures confirmCardSetup()
            // attaches the PaymentMethod to the Stripe customer before returning pm_xxx,
            // so $user->paymentMethods() lists it immediately after storePaymentMethod.
            $intent = $user->createSetupIntent([
                'payment_method_types' => ['card'],
                'usage'                => 'off_session',
            ]);

            return response()->json([
                'client_secret' => $intent->client_secret,
                'stripe_key'    => config('services.stripe.key'),
            ]);
        } catch (\Throwable $e) {
            Log::error('[SETUP_INTENT] failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Could not initialize card entry. ' . $e->getMessage(),
            ], 500);
        }
    }
}
