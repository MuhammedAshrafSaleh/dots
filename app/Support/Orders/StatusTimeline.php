<?php

namespace App\Support\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;

/**
 * Builds the canonical order status ladder (New → Delivered) with each step's
 * state and its matching history timestamp/note. Shared by the public order
 * tracking screen and the customer account order detail so both tell the same
 * story. A cancelled order shows the steps it reached, then a cancelled row.
 */
class StatusTimeline
{
    /** @var list<OrderStatus> */
    public const LADDER = [
        OrderStatus::New,
        OrderStatus::Confirmed,
        OrderStatus::Preparing,
        OrderStatus::Shipped,
        OrderStatus::Delivered,
    ];

    /**
     * @return list<array{status: string, label: string, note: ?string, time: ?string, state: string}>
     */
    public static function build(Order $order): array
    {
        /** @var array<string, OrderStatusHistory> $history */
        $history = $order->statusHistories->keyBy('status')->all();

        $row = function (OrderStatus $status, string $state) use ($history): array {
            $h = $history[$status->value] ?? null;
            $note = $h?->note;

            return [
                'status' => $status->value,
                'label' => __('ui.status_'.$status->value),
                'note' => $note !== null && $note !== '' ? $note : __('ui.status_'.$status->value.'_note'),
                'time' => $h?->created_at?->translatedFormat('j M Y · H:i'),
                'state' => $state,
            ];
        };

        if ($order->status === OrderStatus::Cancelled) {
            $rows = [];
            foreach (self::LADDER as $status) {
                if (isset($history[$status->value])) {
                    $rows[] = $row($status, 'done');
                }
            }
            $rows[] = $row(OrderStatus::Cancelled, 'cancel');

            return $rows;
        }

        $current = self::currentStep($order);

        $rows = [];
        foreach (self::LADDER as $i => $status) {
            $rows[] = $row($status, $i < $current ? 'done' : ($i === $current ? 'now' : 'todo'));
        }

        return $rows;
    }

    /** Zero-based index of the order's current step on the ladder. */
    public static function currentStep(Order $order): int
    {
        $current = array_search($order->status, self::LADDER, true);

        return $current === false ? 0 : $current;
    }
}
