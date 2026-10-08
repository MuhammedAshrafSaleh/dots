<?php

namespace App\Models;

use App\Enums\LeadStatus;
use App\Enums\QuoteSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class QuoteRequest extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'source' => QuoteSource::class,
            'status' => LeadStatus::class,
            'qty' => 'integer',
            'cart_snapshot' => 'array',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
