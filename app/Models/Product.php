<?php

namespace App\Models;

use App\Enums\ProductRelationType;
use App\Enums\ProductStatus;
use App\Enums\StockStatus;
use App\Models\Concerns\HasTranslatableSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

/**
 * @property ProductStatus $status
 * @property StockStatus $stock_status
 * @property bool $price_on_request
 * @property bool $is_new
 * @property bool $is_best_seller
 * @property bool $is_smart_pick
 * @property bool $is_dimmable
 * @property bool $ships_24h
 * @property int $sort_weight
 */
class Product extends Model implements HasMedia
{
    use HasTranslatableSlug;
    use HasTranslations;
    use InteractsWithMedia;

    protected $guarded = ['id'];

    /** @var array<int, string> */
    public array $translatable = [
        'name', 'slug', 'overview', 'variants_summary', 'warranty', 'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'price_on_request' => 'boolean',
            'min_price' => 'decimal:2',
            'max_compare_at' => 'decimal:2',
            'stock_status' => StockStatus::class,
            'is_new' => 'boolean',
            'is_best_seller' => 'boolean',
            'is_smart_pick' => 'boolean',
            'is_dimmable' => 'boolean',
            'ships_24h' => 'boolean',
            'sort_weight' => 'integer',
            'status' => ProductStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery'); // images; caption kept as a custom property
        $this->addMediaCollection('files');   // datasheet / IES / LDT / install guide, with title + type
    }

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<ProductVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    /**
     * Unique swatch hex values for card color dots, drawn from already-loaded
     * variants. Returns [] when 'variants' is not eager-loaded (no lazy query).
     *
     * @return list<string>
     */
    public function cardSwatches(int $max = 5): array
    {
        if (! $this->relationLoaded('variants')) {
            return [];
        }

        return $this->variants
            ->flatMap->swatchAttributeValues
            ->whereNotNull('swatch')
            ->unique('swatch')
            ->take($max)
            ->pluck('swatch')
            ->values()
            ->all();
    }

    /** @return HasMany<ProductSpec, $this> */
    public function specs(): HasMany
    {
        return $this->hasMany(ProductSpec::class)->orderBy('sort_order');
    }

    /**
     * "Frequently bought together".
     *
     * @return BelongsToMany<Product, $this>
     */
    public function relatedProducts(): BelongsToMany
    {
        return $this->relationsOfType(ProductRelationType::Together);
    }

    /**
     * "Don't forget" suggestions in the cart.
     *
     * @return BelongsToMany<Product, $this>
     */
    public function cartSuggestions(): BelongsToMany
    {
        return $this->relationsOfType(ProductRelationType::CartSuggestion);
    }

    /** @return BelongsToMany<Product, $this> */
    protected function relationsOfType(ProductRelationType $type): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'product_relations', 'product_id', 'related_id')
            ->wherePivot('type', $type->value)
            ->withPivot('type', 'sort_order')
            ->orderBy('product_relations.sort_order');
    }

    /**
     * Active, published and visible to the public.
     *
     * @param  Builder<Product>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ProductStatus::Active->value)
            ->where('published_at', '<=', now());
    }

    /** @param  Builder<Product>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', ProductStatus::Active->value);
    }
}
