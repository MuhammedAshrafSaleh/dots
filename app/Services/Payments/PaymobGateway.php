<?php

namespace App\Services\Payments;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Services\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Paymob driver using the Intention / Unified Checkout API.
 *
 * Flow: createCheckout() POSTs an intention (amount, order number as
 * special_reference, notification + redirection URLs) and returns the hosted
 * Unified Checkout URL. Paymob then hits our webhook (server→server) and
 * redirects the customer to our return URL; both carry an HMAC-SHA512 we verify
 * before touching any order. Config comes only from `.env` (config/services).
 */
class PaymobGateway implements PaymentGateway
{
    /**
     * Fields Paymob concatenates (in this exact order) to build the HMAC of a
     * transaction. The webhook sends them nested under `obj`; the browser
     * redirect sends the same values as flat query params (dotted keys).
     *
     * @var list<string>
     */
    private const HMAC_KEYS = [
        'amount_cents', 'created_at', 'currency', 'error_occured',
        'has_parent_transaction', 'id', 'integration_id', 'is_3d_secure',
        'is_auth', 'is_capture', 'is_refunded', 'is_standalone_payment',
        'is_voided', 'order', 'owner', 'pending', 'source_data.pan',
        'source_data.sub_type', 'source_data.type', 'success',
    ];

    public function isConfigured(): bool
    {
        return filled($this->config('secret_key'))
            && filled($this->config('public_key'))
            && filled($this->config('integration_id_card'));
    }

    public function createCheckout(Order $order): string
    {
        $amountCents = (int) round(((float) $order->total) * 100);

        // Refresh the pay-by deadline on every attempt (initial and every retry
        // from the payment-failed page) so the reservation sweeper can never
        // cancel an order mid-payment.
        $order->update([
            'reserved_until' => now()->addMinutes($this->cardWindowMinutes()),
        ]);

        // special_reference must be UNIQUE per intention — Paymob rejects a reused
        // one with HTTP 400 ("An Order with ref: … already exists"), which broke
        // every retry when we sent the bare order number. Suffix a random token so
        // each attempt is distinct; the order number stays embedded (human-readable
        // in the Paymob dashboard). Paymob echoes this back verbatim as
        // merchant_order_id, and we resolve the order by an exact lookup on the
        // stored value (see orderForReference()) — never by parsing it.
        $reference = $order->number.'-'.Str::upper(Str::random(12));

        $response = Http::withToken($this->config('secret_key'))
            ->acceptJson()
            ->post($this->baseUrl().'/v1/intention/', [
                'amount' => $amountCents,
                'currency' => 'EGP',
                'payment_methods' => [(int) $this->config('integration_id_card')],
                'special_reference' => $reference,
                'notification_url' => route('paymob.webhook'),
                'redirection_url' => route('paymob.return', [$order->locale ?: app()->getLocale()]),
                'items' => [[
                    'name' => 'Order '.$order->number,
                    'amount' => $amountCents,
                    'quantity' => 1,
                ]],
                'billing_data' => $this->billingData($order),
            ]);

        if ($response->failed() || blank($response->json('client_secret'))) {
            Log::warning('Paymob intention failed', ['order' => $order->number, 'status' => $response->status()]);
            throw new PaymentException('ui.pf_gateway_error');
        }

        // Only write the payment row once Paymob has ACCEPTED the intention, so a
        // rejected attempt leaves no orphan pending row behind.
        $order->payments()->create([
            'gateway' => 'paymob',
            'gateway_ref' => (string) $response->json('id'),
            'gateway_reference' => $reference,
            'amount' => $order->total,
            'currency' => 'EGP',
            'status' => PaymentStatus::Pending,
        ]);

        return $this->baseUrl().'/unifiedcheckout/?publicKey='.$this->config('public_key')
            .'&clientSecret='.$response->json('client_secret');
    }

    /**
     * Verify a transaction coming from the webhook (nested `obj`). Returns the
     * verified transaction array, or null when the HMAC doesn't match.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function verifyWebhook(array $payload, ?string $hmac): ?array
    {
        $obj = $payload['obj'] ?? null;
        if (! is_array($obj) || $hmac === null) {
            return null;
        }

        $values = [];
        foreach (self::HMAC_KEYS as $key) {
            $values[] = $this->dotFromObj($obj, $key === 'order' ? 'order.id' : $key);
        }

        return $this->hmacMatches($values, $hmac) ? $obj : null;
    }

    /**
     * Verify the browser-redirect callback (flat query params). Returns a
     * normalised transaction array shaped like the webhook `obj`, or null.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>|null
     */
    public function verifyCallback(array $query): ?array
    {
        $hmac = isset($query['hmac']) ? (string) $query['hmac'] : null;
        if ($hmac === null) {
            return null;
        }

        // PHP (and Symfony) rewrite dots in query-param names to underscores, so
        // Paymob's `source_data.pan` arrives as `source_data_pan`. Look up either.
        $get = fn (string $key) => $query[$key] ?? $query[str_replace('.', '_', $key)] ?? null;

        $values = [];
        foreach (self::HMAC_KEYS as $key) {
            $values[] = $get($key);
        }

        if (! $this->hmacMatches($values, $hmac)) {
            return null;
        }

        // Reshape the flat callback back into the webhook `obj` structure so
        // recordTransaction() has one code path.
        return [
            'id' => $get('id'),
            'success' => $this->asBool($get('success')),
            'amount_cents' => $get('amount_cents'),
            'order' => ['id' => $get('order'), 'merchant_order_id' => $get('merchant_order_id')],
            'source_data' => [
                'pan' => $get('source_data.pan'),
                'sub_type' => $get('source_data.sub_type'),
                'type' => $get('source_data.type'),
            ],
            'data' => ['message' => $get('data.message')],
            'txn_response_code' => $get('txn_response_code'),
        ];
    }

    /**
     * Idempotently record a verified transaction against its order: write a
     * webhook_events row (skip if already seen), upsert the payment and update
     * the order's payment status + history.
     *
     * @param  array<string, mixed>  $obj  webhook `obj` (or callback reshaped to it)
     */
    public function recordTransaction(array $obj, bool $hmacVerified): void
    {
        $transactionId = isset($obj['id']) ? (string) $obj['id'] : null;
        $merchantRef = $obj['order']['merchant_order_id'] ?? null;
        if ($transactionId === null || ! is_string($merchantRef) || blank($merchantRef)) {
            return;
        }

        $resolved = $this->orderForReference($merchantRef);
        if ($resolved === null) {
            return;
        }
        $orderId = $resolved->id;

        $success = $this->asBool($obj['success'] ?? null);
        $pan = isset($obj['source_data']['pan']) ? (string) $obj['source_data']['pan'] : null;

        DB::transaction(function () use ($orderId, $obj, $transactionId, $success, $pan, $hmacVerified): void {
            // The idempotency marker lives INSIDE this transaction: if anything
            // below throws, the webhook_events row rolls back with it, so
            // Paymob's retry of the same event is re-processed rather than
            // swallowed as a duplicate. A genuinely already-committed event is
            // still a no-op (firstOrCreate finds the existing row).
            $event = WebhookEvent::firstOrCreate(
                ['provider' => 'paymob', 'event_id' => $transactionId],
                ['payload' => $obj, 'processed_at' => now()],
            );

            if (! $event->wasRecentlyCreated) {
                return; // duplicate delivery — already processed
            }

            // Lock the order row for the life of the transaction so an in-flight
            // reservation sweep cannot cancel it between our read and our write.
            $order = Order::whereKey($orderId)->lockForUpdate()->first();
            if ($order === null) {
                return;
            }

            // Money may land after the reservation sweeper already cancelled the
            // order (pathological late retry — Paymob normally fires within
            // seconds). Judged from the LOCKED row so a concurrent sweep that
            // committed first is seen. We record the payment and flag it for
            // manual review, but never un-cancel the order or move stock.
            $lateAfterCancel = $success && $order->status === OrderStatus::Cancelled;

            Payment::updateOrCreate(
                ['order_id' => $order->id, 'transaction_id' => $transactionId],
                [
                    'gateway' => 'paymob',
                    'amount' => isset($obj['amount_cents']) ? ((int) $obj['amount_cents']) / 100 : $order->total,
                    'currency' => 'EGP',
                    'status' => $success ? PaymentStatus::Paid : PaymentStatus::Failed,
                    'card_brand' => $obj['source_data']['sub_type'] ?? null,
                    'card_last4' => $pan !== null ? substr(preg_replace('/\D/', '', $pan) ?: $pan, -4) : null,
                    'failure_code' => $success ? null : ($obj['txn_response_code'] ?? null),
                    'failure_message' => $success ? null : ($obj['data']['message'] ?? null),
                    'raw' => $obj,
                    'hmac_verified' => $hmacVerified,
                ],
            );

            // On success clear the pay-by deadline so a paid order drops out of
            // the sweep immediately. Leave it set on failure so the sweeper can
            // still collect the unpaid stock. Never un-cancel a cancelled order.
            $attributes = ['payment_status' => $success ? OrderPaymentStatus::Paid : OrderPaymentStatus::Failed];
            if ($success) {
                $attributes['reserved_until'] = null;
            }
            $order->update($attributes);

            $note = match (true) {
                $lateAfterCancel => [
                    'en' => 'Card payment received AFTER auto-cancellation — review and refund or re-fulfil.',
                    'ar' => 'تم استلام الدفع بالبطاقة بعد الإلغاء التلقائي — يُرجى المراجعة والاسترجاع أو إعادة التجهيز.',
                ],
                $success => ['en' => 'Card payment received via Paymob.', 'ar' => 'تم استلام الدفع بالبطاقة عبر Paymob.'],
                default => ['en' => 'Card payment failed at Paymob.', 'ar' => 'فشل الدفع بالبطاقة في Paymob.'],
            };

            $order->statusHistories()->create([
                'status' => $order->status->value,
                'note' => $note,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Resolve the order a verified transaction belongs to from the echoed
     * `merchant_order_id`. The ONLY supported path is an exact match on
     * `payments.gateway_reference` — the unique special_reference we stored when
     * the intention was accepted, which Paymob returns verbatim. This never parses
     * the order number, so the admin-editable order prefix cannot misroute money.
     */
    public function orderForReference(string $merchantRef): ?Order
    {
        $payment = Payment::where('gateway_reference', $merchantRef)->latest('id')->first();
        if ($payment !== null) {
            return $payment->order;
        }

        // Transitional fallback: intentions created before PAY.14 were sent with
        // the bare order number and have no stored gateway_reference. This is NOT a
        // supported path for new intentions — remove once no such intention can
        // still be in flight.
        return Order::where('number', $merchantRef)->first();
    }

    /** @param  list<mixed>  $values */
    private function hmacMatches(array $values, string $hmac): bool
    {
        $string = implode('', array_map(fn ($v) => $this->stringify($v), $values));
        $computed = hash_hmac('sha512', $string, (string) $this->config('hmac_secret'));

        return hash_equals($computed, strtolower($hmac));
    }

    /** @param  array<string, mixed>  $obj */
    private function dotFromObj(array $obj, string $path): mixed
    {
        return data_get($obj, $path);
    }

    private function stringify(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if ($value === null) {
            return '';
        }

        return (string) $value;
    }

    private function asBool(mixed $value): bool
    {
        return $value === true || $value === 'true' || $value === 1 || $value === '1';
    }

    /** @return array<string, string> */
    private function billingData(Order $order): array
    {
        $parts = preg_split('/\s+/', trim((string) $order->contact_name), 2) ?: [];
        $address = $order->address()->first();

        return [
            'first_name' => $parts[0] ?? 'NA',
            'last_name' => $parts[1] ?? ($parts[0] ?? 'NA'),
            'phone_number' => (string) $order->contact_mobile,
            'email' => (string) ($order->contact_email ?? 'NA'),
            'country' => 'EG',
            'city' => $address->city ?? 'NA',
            'state' => $address !== null ? (string) $address->governorate_name : 'NA',
            'street' => $address->street ?? 'NA',
            'building' => 'NA',
            'floor' => $address->floor_apartment ?? 'NA',
            'apartment' => 'NA',
            'postal_code' => 'NA',
        ];
    }

    private function cardWindowMinutes(): int
    {
        return (int) app(Settings::class)->get('commerce.card_payment_window_minutes', 60);
    }

    private function config(string $key): ?string
    {
        $value = config('services.paymob.'.$key);

        return $value !== null ? (string) $value : null;
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.paymob.base_url', 'https://accept.paymob.com'), '/');
    }
}
