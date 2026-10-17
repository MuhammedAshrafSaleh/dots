<?php

namespace App\Services\Payments;

use App\Models\Order;

/**
 * A card payment gateway. Implemented by PaymobGateway; bound in
 * AppServiceProvider so checkout/payment-failed can stay driver-agnostic.
 */
interface PaymentGateway
{
    /** True only when every credential the driver needs is present in `.env`. */
    public function isConfigured(): bool;

    /**
     * Start a hosted checkout for the order and return the URL to send the
     * customer's browser to. Creates a pending `payments` row for the attempt.
     *
     * @throws PaymentException when the gateway rejects the request
     */
    public function createCheckout(Order $order): string;
}
