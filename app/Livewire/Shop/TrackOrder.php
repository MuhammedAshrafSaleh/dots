<?php

namespace App\Livewire\Shop;

use App\Models\Order;
use App\Support\Orders\StatusTimeline;
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

    public function render(): View
    {
        $order = $this->searched && $this->foundOrderId !== null ? $this->findOrder() : null;

        return view('livewire.shop.track-order', [
            'order' => $order,
            'timeline' => $order !== null ? StatusTimeline::build($order) : [],
            'stepIndex' => $order !== null ? StatusTimeline::currentStep($order) : 0,
            'stepCount' => count(StatusTimeline::LADDER),
            'locale' => app()->getLocale(),
        ]);
    }
}
