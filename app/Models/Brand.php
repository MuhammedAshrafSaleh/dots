<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class Brand extends Model implements HasMedia
{
    use HasTranslations;
    use InteractsWithMedia;

    protected $guarded = ['id'];

    /**
     * Enforce a single featured brand: whenever one is saved as featured,
     * clear the flag on every other brand (via a query, so no event recursion).
     */
    protected static function booted(): void
    {
        static::saved(function (Brand $brand): void {
            if ($brand->is_featured) {
                static::query()
                    ->whereKeyNot($brand->getKey())
                    ->where('is_featured', true)
                    ->update(['is_featured' => false]);
            }
        });
    }

    /** Slug is a single non-translated string for brands. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @var array<int, string> */
    public array $translatable = [
        'origin_country', 'description', 'feature_text', 'catalogue_label', 'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_authorised' => 'boolean',
            'show_in_partners' => 'boolean',
            'show_on_home' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
        $this->addMediaCollection('cover')->singleFile();     // brand-page header
        $this->addMediaCollection('feature')->singleFile();   // "signature" banner image
        $this->addMediaCollection('catalogue')->singleFile(); // PDF
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** @param  Builder<Brand>  $query */
    public function scopeActive($query): void
    {
        $query->where('is_active', true);
    }
}
