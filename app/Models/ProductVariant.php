<?php

namespace App\Models;

use App\Enums\AttributeDisplay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property bool $is_default
 * @property bool $is_active
 * @property int $stock_qty
 * @property int|null $low_stock_threshold
 */
class ProductVariant extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'stock_qty' => 'integer',
            'low_stock_threshold' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsToMany<AttributeValue, $this> */
    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'product_variant_values');
    }

    /**
     * Only the swatch-display attribute values (for card color dots).
     *
     * @return BelongsToMany<AttributeValue, $this>
     */
    public function swatchAttributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'product_variant_values')
            ->whereHas('attribute', fn ($q) => $q->where('display', AttributeDisplay::Swatch->value))
            ->whereNotNull('swatch');
    }

    /** @param  Builder<ProductVariant>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
