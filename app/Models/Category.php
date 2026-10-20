<?php

namespace App\Models;

use App\Models\Concerns\HasImageConversions;
use App\Models\Concerns\HasTranslatableSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class Category extends Model implements HasMedia
{
    use HasImageConversions;
    use HasTranslatableSlug;
    use HasTranslations;
    use InteractsWithMedia;

    protected $guarded = ['id'];

    /** @var array<int, string> */
    public array $translatable = [
        'name', 'slug', 'description', 'buying_guide_title', 'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'show_on_home' => 'boolean',
            'show_in_footer' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();   // card image
        $this->addMediaCollection('hero')->singleFile();    // category-page background
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerResponsiveImageConversions();
    }

    /** @return array<int, string> */
    public function imageConversionCollections(): array
    {
        return ['image', 'hero'];
    }

    /** @return BelongsTo<Category, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Category, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** @return BelongsToMany<Product, $this> */
    public function featuredProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_featured_products')
            ->withPivot('sort_order')
            ->orderBy('category_featured_products.sort_order');
    }

    /** @return MorphMany<Faq, $this> */
    public function faqs(): MorphMany
    {
        return $this->morphMany(Faq::class, 'faqable')->orderBy('sort_order');
    }

    /** @param  Builder<Category>  $query */
    public function scopeActive($query): void
    {
        $query->where('is_active', true);
    }
}
