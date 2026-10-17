<x-mail::message>
# New order {{ $order->number }}

**Customer:** {{ $order->contact_name }} · {{ $order->contact_mobile }}

**Payment:** {{ $order->payment_method_code }} · **Status:** {{ $order->payment_status->value }}

**Total:** EGP {{ number_format($order->total, 2) }}

@if($order->relationLoaded('items') && $order->items->isNotEmpty())
<x-mail::table>
| Item | Qty | Price |
|------|----:|------:|
@foreach($order->items as $item)
| {{ $item->product_name['en'] ?? '' }} | {{ $item->qty }} | EGP {{ number_format($item->line_total, 2) }} |
@endforeach
</x-mail::table>
@endif

<x-mail::button :url="route('admin.orders.show', $order)">
View order in admin
</x-mail::button>
</x-mail::message>
