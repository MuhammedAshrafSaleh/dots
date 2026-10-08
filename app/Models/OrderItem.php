<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class OrderItem extends Model
{
    use HasTranslations;

    public $timestamps = false;

    protected $guarded = ['id'];

    /** @var array<int, string> */
    public array $translatable = ['product_name', 'variant_label'];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'qty' => 'integer',
            'line_total' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
