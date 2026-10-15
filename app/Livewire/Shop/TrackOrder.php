<?php

namespace App\Livewire\Shop;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Public order tracking: look an order up by its number AND the mobile it was
 * placed with (both must match — this is the only authentication), then show a
 * timeline built from its status history plus the expected delivery date.
 */
class TrackOrder extends Component
{
    #[Url]
    public string $number = '';

    public string $mobile = '';

    /** True once a lookup has run, so the view can show a result or empty state. */
    public bool $searched = false;

    public ?int $foundOrderId = null;

    public function mount(): void
    {
        // Deep-link from the confirmation page: ?number=DT-10001 prefills the box.
        if ($this->number !== '') {
            $this->number = trim($this->number);
        }
    }

    public function track(): void
    {
        $this->validate([
            'number' => ['required', 'string', 'max:40'],
            'mobile' => ['required', 'string', 'max:30'],
        ]);

        $this->searched = true;
        $this->foundOrderId = $this->findOrder()?->id;
    }

    /** Both the number and the exact mobile must match; neither alone is enough. */
    private function findOrder(): ?Order
    {
        $number = strtoupper(trim($this->number));
        $mobile = preg_replace('/\D/', '', $this->mobile) ?? '';

        if ($number === '' || $mobile === '') {
            return null;
        }

        return Order::where('number', $number)
            ->where('contact_mobile', $mobile)
            ->with(['items', 'statusHistories', 'address'])
            ->first();
    }

    /**
     * Canonical status ladder with each step's state + the matching history
     * row's timestamp/note. A cancelled order shows the steps it reached, then a
     * cancelled row.
     *
     * @return list<array{status: string, label: string, note: ?string, time: ?string, state: string}>
     */
    private function timeline(Order $order): array
    {
        /** @var array<string, OrderStatusHistory> $history */
        $history = $order->statusHistories->keyBy('status')->all();

        $ladder = [
            OrderStatus::New,
            OrderStatus::Confirmed,
            OrderStatus::Preparing,
            OrderStatus::Shipped,
            OrderStatus::Delivered,
        ];

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
            foreach ($ladder as $status) {
                if (isset($history[$status->value])) {
                    $rows[] = $row($status, 'done');
                }
            }
            $rows[] = $row(OrderStatus::Cancelled, 'cancel');

            return $rows;
        }

        $current = array_search($order->status, $ladder, true);
        $current = $current === false ? 0 : $current;

        $rows = [];
        foreach ($ladder as $i => $status) {
            $rows[] = $row($status, $i < $current ? 'done' : ($i === $current ? 'now' : 'todo'));
        }

        return $rows;
    }

    public function render(): View
    {
        $order = $this->searched && $this->foundOrderId !== null ? $this->findOrder() : null;

        return view('livewire.shop.track-order', [
            'order' => $order,
            'timeline' => $order !== null ? $this->timeline($order) : [],
            'stepIndex' => $order !== null ? $this->currentStep($order) : 0,
            'stepCount' => 5,
            'locale' => app()->getLocale(),
        ]);
    }

    private function currentStep(Order $order): int
    {
        return match ($order->status) {
            OrderStatus::New => 0,
            OrderStatus::Confirmed => 1,
            OrderStatus::Preparing => 2,
            OrderStatus::Shipped => 3,
            OrderStatus::Delivered => 4,
            OrderStatus::Cancelled => 0,
        };
    }
}
