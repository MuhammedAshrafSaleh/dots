<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Livewire\Admin\Concerns\WithAdminTable;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use WithAdminTable;

    #[Url(except: 'all', history: false)]
    public string $tab = 'all';

    #[Url(except: '', history: false)]
    public string $payment = '';

    #[Url(except: '', history: false)]
    public string $method = '';

    #[Url(except: '', history: false)]
    public string $from = '';

    #[Url(except: '', history: false)]
    public string $to = '';

    public function updatingTab(): void
    {
        $this->resetPage();
    }

    public function updatingPayment(): void
    {
        $this->resetPage();
    }

    public function updatingMethod(): void
    {
        $this->resetPage();
    }

    public function updatingFrom(): void
    {
        $this->resetPage();
    }

    public function updatingTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'payment', 'method', 'from', 'to', 'tab');
        $this->resetPage();
    }

    /**
     * Base query shared by the table and the CSV export.
     *
     * @return Builder<Order>
     */
    private function baseQuery(): Builder
    {
        return Order::query()
            ->with(['items', 'address.governorate'])
            ->withCount('items as items_count')
            ->when($this->tab !== 'all', fn (Builder $q) => $q->where('status', $this->tab))
            ->when($this->payment !== '', fn (Builder $q) => $q->where('payment_status', $this->payment))
            ->when($this->method !== '', fn (Builder $q) => $q->where('payment_method_code', $this->method))
            ->when($this->from !== '', fn (Builder $q) => $q->whereDate('placed_at', '>=', $this->from))
            ->when($this->to !== '', fn (Builder $q) => $q->whereDate('placed_at', '<=', $this->to))
            ->when($this->search !== '', function (Builder $q) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim($this->search)).'%';
                $q->where(fn (Builder $w) => $w->where('number', 'like', $like)
                    ->orWhere('contact_name', 'like', $like)
                    ->orWhere('contact_mobile', 'like', $like));
            })
            ->orderByDesc('placed_at')
            ->orderByDesc('id');
    }

    /** @return array<string, int> */
    public function tabCounts(): array
    {
        $counts = Order::query()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $out = ['all' => (int) $counts->sum()];
        foreach (OrderStatus::cases() as $case) {
            $out[$case->value] = (int) ($counts[$case->value] ?? 0);
        }

        return $out;
    }

    public function export(): StreamedResponse
    {
        $this->authorize('orders.view');

        $locale = app()->getLocale();
        $filename = 'orders-'.now()->format('Y-m-d').'.csv';

        $rows = $this->baseQuery()->get();

        return response()->streamDownload(function () use ($rows, $locale): void {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel reads Arabic correctly.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                __('admin.orders.col_order', [], $locale),
                __('admin.orders.col_date', [], $locale),
                __('admin.orders.col_customer', [], $locale),
                __('admin.orders.col_mobile', [], $locale),
                __('admin.orders.col_governorate', [], $locale),
                __('admin.orders.col_items', [], $locale),
                __('admin.orders.col_total', [], $locale),
                __('admin.orders.col_method', [], $locale),
                __('admin.orders.col_payment', [], $locale),
                __('admin.orders.col_status', [], $locale),
            ]);

            foreach ($rows as $order) {
                fputcsv($out, [
                    $order->number,
                    $order->placed_at?->format('Y-m-d H:i'),
                    $order->contact_name,
                    $order->contact_mobile,
                    $order->address?->governorate?->getTranslation('name', $locale, false),
                    $order->items_count,
                    number_format((float) $order->total, 2, '.', ''),
                    __('admin.orders.method_'.$order->payment_method_code, [], $locale),
                    __('admin.orders.pay_'.$order->payment_status->value, [], $locale),
                    __('admin.orders.status_'.$order->status->value, [], $locale),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render(): View
    {
        return view('livewire.admin.orders.index', [
            'rows' => $this->baseQuery()->paginate(12),
            'counts' => $this->tabCounts(),
            'paymentStatuses' => array_map(
                fn (OrderPaymentStatus $s) => ['value' => $s->value, 'label' => __('admin.orders.pay_'.$s->value)],
                OrderPaymentStatus::cases(),
            ),
            'methods' => [
                ['value' => 'cod', 'label' => __('admin.orders.method_cod')],
                ['value' => 'card', 'label' => __('admin.orders.method_card')],
                ['value' => 'instapay_whatsapp', 'label' => __('admin.orders.method_instapay_whatsapp')],
            ],
        ]);
    }
}
