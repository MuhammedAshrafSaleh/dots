<?php

namespace App\Models;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Services\Settings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Translatable\HasTranslations;

class Order extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    /** @var array<int, string> */
    public array $translatable = ['delivery_method_name'];

    protected function casts(): array
    {
        return [
            'is_company' => 'boolean',
            'status' => OrderStatus::class,
            'payment_status' => OrderPaymentStatus::class,
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'expected_delivery_at' => 'date',
            'reserved_until' => 'datetime',
            'placed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // number = order_prefix + id, generated once right after insert. The id
        // is a unique auto-increment (starting at 10001), so numbers are unique
        // without an extra lock; the unique index on `number` is the backstop.
        static::created(function (Order $order): void {
            if ($order->number === null) {
                $prefix = (string) (app(Settings::class)->get('commerce.order_prefix', 'DT-') ?? 'DT-');
                $order->number = $prefix.$order->id;
                $order->saveQuietly();
            }
        });
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<PaymentMethod, $this> */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /** @return BelongsTo<DeliveryMethod, $this> */
    public function deliveryMethod(): BelongsTo
    {
        return $this->belongsTo(DeliveryMethod::class);
    }

    /** @return BelongsTo<Coupon, $this> */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasOne<OrderAddress, $this> */
    public function address(): HasOne
    {
        return $this->hasOne(OrderAddress::class);
    }

    /** @return HasMany<OrderStatusHistory, $this> */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at');
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @param  Builder<Order>  $query */
    public function scopeGuest(Builder $query): void
    {
        $query->whereNull('customer_id');
    }
}
