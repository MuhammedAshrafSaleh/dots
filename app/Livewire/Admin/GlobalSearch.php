<?php

namespace App\Livewire\Admin;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Topbar global search (ATopbar): orders by number, products by SKU/name,
 * customers by name/email/mobile. Results link to each module's screen when
 * that route exists (built across later tasks); otherwise they stay inert.
 */
class GlobalSearch extends Component
{
    #[Url(except: '', history: false)]
    public string $q = '';

    public bool $open = false;

    public function updatedQ(): void
    {
        $this->open = trim($this->q) !== '';
    }

    public function clear(): void
    {
        $this->reset('q', 'open');
    }

    /**
     * @return array{orders: list<array<string, mixed>>, products: list<array<string, mixed>>, customers: list<array<string, mixed>>, total: int}
     */
    private function results(): array
    {
        $term = trim($this->q);

        if (mb_strlen($term) < 2) {
            return ['orders' => [], 'products' => [], 'customers' => [], 'total' => 0];
        }

        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term).'%';

        $orders = Order::query()
            ->where(fn ($q) => $q->where('number', 'like', $like)
                ->orWhere('contact_name', 'like', $like)
                ->orWhere('contact_mobile', 'like', $like))
            ->latest('id')->limit(5)->get()
            ->map(fn (Order $o) => [
                'label' => $o->number,
                'meta' => $o->contact_name,
                'url' => $this->urlFor(['admin.orders.show' => $o->id, 'admin.orders' => null]),
            ])->all();

        $products = Product::query()
            ->where(fn ($q) => $q->where('sku', 'like', $like)
                ->orWhere('search_text', 'like', $like)
                ->orWhereHas('variants', fn ($v) => $v->where('sku', 'like', $like)))
            ->limit(5)->get()
            ->map(fn (Product $p) => [
                'label' => $p->getTranslation('name', app()->getLocale(), false) ?: $p->sku,
                'meta' => $p->sku,
                'url' => $this->urlFor(['admin.products.edit' => $p->id, 'admin.products' => null]),
            ])->all();

        $customers = Customer::query()
            ->where(fn ($q) => $q->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('mobile', 'like', $like))
            ->limit(5)->get()
            ->map(fn (Customer $c) => [
                'label' => $c->name,
                'meta' => $c->mobile,
                'url' => $this->urlFor(['admin.customers.show' => $c->id, 'admin.customers' => null]),
            ])->all();

        return [
            'orders' => $orders,
            'products' => $products,
            'customers' => $customers,
            'total' => count($orders) + count($products) + count($customers),
        ];
    }

    /**
     * First existing route from [name => param]; null if none exists yet.
     *
     * @param  array<string, int|null>  $candidates
     */
    private function urlFor(array $candidates): ?string
    {
        foreach ($candidates as $name => $param) {
            if (Route::has($name)) {
                return $param === null ? route($name) : route($name, $param);
            }
        }

        return null;
    }

    public function render(): View
    {
        return view('livewire.admin.global-search', [
            'results' => $this->results(),
        ]);
    }
}
