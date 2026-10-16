<?php

namespace App\Livewire\Admin;

use App\Enums\OrderPaymentStatus;
use App\Enums\StockStatus;
use App\Models\ConsultationRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\QuoteRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Dashboard extends Component
{
    public function render(): View
    {
        $user = Auth::guard('admin')->user();
        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        return view('livewire.admin.dashboard', [
            'name' => $user?->name,
            'ordersToday' => Order::query()->where('placed_at', '>=', $today)->count(),
            'revenueMonth' => (float) Order::query()
                ->where('payment_status', OrderPaymentStatus::Paid)
                ->where('placed_at', '>=', $monthStart)
                ->sum('total'),
            'whatsappToday' => QuoteRequest::query()->where('created_at', '>=', $today)->count(),
            'consultationsNew' => ConsultationRequest::query()->where('status', 'new')->count(),
            'recentOrders' => Order::query()
                ->orderByDesc('placed_at')->orderByDesc('id')
                ->limit(7)->get(),
            'lowStock' => Product::query()
                ->where('stock_status', StockStatus::Low)
                ->withSum('variants as stock_total', 'stock_qty')
                ->orderBy('stock_total')
                ->limit(5)->get(),
            'latestLeads' => QuoteRequest::query()
                ->with('productVariant.product')
                ->orderByDesc('created_at')->orderByDesc('id')
                ->limit(4)->get(),
        ]);
    }
}
