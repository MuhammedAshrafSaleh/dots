@props(['order'])
@php
    use App\Enums\OrderStatus;
    use App\Enums\OrderPaymentStatus;

    /** @var \App\Models\Order $order */
    $statusTone = match (true) {
        $order->status === OrderStatus::Cancelled => ['error', __('ui.status_cancelled')],
        $order->status === OrderStatus::Delivered => ['success', __('ui.status_delivered')],
        default => ['dark', __('ui.status_'.$order->status->value)],
    };

    $payTone = match ($order->payment_status) {
        OrderPaymentStatus::Paid => ['success', __('ui.pay_status_paid')],
        OrderPaymentStatus::AwaitingTransfer => ['warning', __('ui.pay_status_awaiting')],
        OrderPaymentStatus::Failed => ['error', __('ui.pay_status_failed')],
        OrderPaymentStatus::Refunded => ['neutral', __('ui.pay_status_refunded')],
        default => ['neutral', __('ui.pay_status_pending')],
    };
@endphp
<div {{ $attributes->merge(['style' => 'display:flex;gap:8px;flex-wrap:wrap;']) }}>
    <x-badge :tone="$statusTone[0]" dot>{{ $statusTone[1] }}</x-badge>
    <x-badge :tone="$payTone[0]" dot>{{ $payTone[1] }}</x-badge>
</div>
