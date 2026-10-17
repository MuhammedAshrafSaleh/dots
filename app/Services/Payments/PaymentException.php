<?php

namespace App\Services\Payments;

use RuntimeException;

/** Thrown when the gateway rejects a checkout request (network/credentials/API). */
class PaymentException extends RuntimeException {}
