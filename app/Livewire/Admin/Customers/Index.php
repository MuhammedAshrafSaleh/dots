<?php

namespace App\Livewire\Admin\Customers;

use App\Enums\CustomerStatus;
use App\Livewire\Admin\Concerns\WithAdminTable;
use App\Models\Customer;
use App\Models\Governorate;
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

    #[Url(except: '', history: false)]
    public string $status = '';

    #[Url(except: '', history: false)]
    public string $gov = '';

    #[Url(except: '', history: false)]
    public string $orders = '';

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingGov(): void
    {
        $this->resetPage();
    }

    public function updatingOrders(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'gov', 'orders');
        $this->resetPage();
    }

    /**
     * Base query shared by the table and the CSV export.
     *
     * @return Builder<Customer>
     */
    private function baseQuery(): Builder
    {
        return Customer::query()
            ->withCount('orders')
            ->withSum('orders', 'total')
            ->when($this->status !== '', fn (Builder $q) => $q->where('status', $this->status))
            ->when($this->gov !== '', fn (Builder $q) => $q->whereHas('addresses', fn (Builder $a) => $a->where('governorate_id', $this->gov)))
            ->when($this->orders === 'y', fn (Builder $q) => $q->has('orders'))
            ->when($this->orders === 'n', fn (Builder $q) => $q->doesntHave('orders'))
            ->when($this->search !== '', function (Builder $q) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim($this->search)).'%';
                $q->where(fn (Builder $w) => $w->where('name', 'like', $like)
                    ->orWhere('mobile', 'like', $like)
                    ->orWhere('email', 'like', $like));
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function delete(int $id): void
    {
        $this->authorize('customers.delete');

        Customer::findOrFail($id)->delete();

        $this->dispatch('toast', tone: 'success', title: __('admin.customers.deleted'));
    }

    public function export(): StreamedResponse
    {
        $this->authorize('customers.view');

        $locale = app()->getLocale();
        $filename = 'customers-'.now()->format('Y-m-d').'.csv';

        $rows = $this->baseQuery()->get();

        return response()->streamDownload(function () use ($rows, $locale): void {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel reads Arabic correctly.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                __('admin.customers.col_customer', [], $locale),
                __('admin.customers.col_mobile', [], $locale),
                __('admin.customers.col_email', [], $locale),
                __('admin.customers.col_orders', [], $locale),
                __('admin.customers.col_spent', [], $locale),
                __('admin.customers.col_joined', [], $locale),
                __('admin.customers.col_status', [], $locale),
            ]);

            foreach ($rows as $customer) {
                fputcsv($out, [
                    $customer->name,
                    $customer->mobile,
                    $customer->email,
                    (int) $customer->orders_count,
                    number_format((float) $customer->getAttribute('orders_sum_total'), 2, '.', ''),
                    $customer->created_at?->format('Y-m-d'),
                    __('admin.customers.status_'.$customer->status->value, [], $locale),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render(): View
    {
        return view('livewire.admin.customers.index', [
            'rows' => $this->baseQuery()->paginate(12),
            'statuses' => array_map(
                fn (CustomerStatus $s) => ['value' => $s->value, 'label' => __('admin.customers.status_'.$s->value)],
                CustomerStatus::cases(),
            ),
            'governorates' => Governorate::query()
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Governorate $g) => ['value' => $g->id, 'label' => $g->getTranslation('name', app()->getLocale(), false)])
                ->all(),
        ]);
    }
}
