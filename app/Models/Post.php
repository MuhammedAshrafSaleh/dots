<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Models\Concerns\HasImageConversions;
use App\Models\Concerns\HasTranslatableSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * @property PostStatus $status
 * @property Carbon|null $published_at
 */
class Post extends Model implements HasMedia
{
    use HasImageConversions;
    use HasTranslatableSlug;
    use HasTranslations;
    use InteractsWithMedia;

    protected $guarded = ['id'];

    /** @var array<int, string> */
    public array $translatable = [
        'title', 'slug', 'excerpt', 'body', 'author_name', 'author_role', 'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'read_minutes' => 'integer',
            'is_featured' => 'boolean',
            'status' => PostStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
        $this->addMediaCollection('author')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerResponsiveImageConversions();
    }

    /** @return array<int, string> */
    public function imageConversionCollections(): array
    {
        return ['cover', 'author'];
    }

    /** @return BelongsTo<PostCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tag');
    }

    /** @return BelongsToMany<Product, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'post_product')
            ->withPivot('sort_order')
            ->orderBy('post_product.sort_order');
    }

    /**
     * Published and visible to the public.
     *
     * @param  Builder<Post>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PostStatus::Published->value)
            ->where('published_at', '<=', now());
    }
}
