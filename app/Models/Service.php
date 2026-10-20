<?php

namespace App\Models;

use App\Models\Concerns\HasImageConversions;
use App\Models\Concerns\HasTranslatableSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class Service extends Model implements HasMedia
{
    use HasImageConversions;
    use HasTranslatableSlug;
    use HasTranslations;
    use InteractsWithMedia;

    protected $guarded = ['id'];

    /** @var array<int, string> */
    public array $translatable = [
        'title', 'slug', 'excerpt', 'description', 'included_title', 'duration_text', 'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'show_in_footer' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
        $this->addMediaCollection('gallery'); // 3 images beside "what's included"
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerResponsiveImageConversions();
    }

    /** @return array<int, string> */
    public function imageConversionCollections(): array
    {
        return ['image', 'gallery'];
    }

    /** @return HasMany<ServiceItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ServiceItem::class)->orderBy('sort_order');
    }

    /** @return BelongsToMany<Project, $this> */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_service');
    }

    /** @return MorphMany<Faq, $this> */
    public function faqs(): MorphMany
    {
        return $this->morphMany(Faq::class, 'faqable')->orderBy('sort_order');
    }

    /** @param  Builder<Service>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
