<?php

namespace App\Http\Controllers;

use App\Services\Payments\PaymobGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Server→server transaction callback from Paymob. Verifies the HMAC, then
 * idempotently records the transaction. Always answers 200 so Paymob does not
 * retry a payload we have deliberately ignored (bad HMAC, unknown order).
 */
class PaymobWebhookController extends Controller
{
    public function __invoke(Request $request, PaymobGateway $gateway): JsonResponse
    {
        $hmac = $request->query('hmac') ?? $request->input('hmac');
        $obj = $gateway->verifyWebhook($request->all(), is_string($hmac) ? $hmac : null);

        if ($obj === null) {
            return response()->json(['ok' => false], 200);
        }

        $gateway->recordTransaction($obj, hmacVerified: true);

        return response()->json(['ok' => true]);
    }
}
