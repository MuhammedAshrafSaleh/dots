<?php

namespace App\Models;

use App\Enums\ProjectStage;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Concerns\HasTranslatableSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class Project extends Model implements HasMedia
{
    use HasTranslatableSlug;
    use HasTranslations;
    use InteractsWithMedia;

    protected $guarded = ['id'];

    /** @var array<int, string> */
    public array $translatable = [
        'title', 'slug', 'location', 'client', 'phase_label', 'scope_summary',
        'about', 'challenge', 'solution', 'result', 'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'type' => ProjectType::class,
            'stage' => ProjectStage::class,
            'start_date' => 'date',
            'expected_completion' => 'date',
            'completed_year' => 'integer',
            'progress' => 'integer',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
        $this->addMediaCollection('gallery');
    }

    /** @return BelongsToMany<Product, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'project_product');
    }

    /** @return BelongsToMany<Service, $this> */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'project_service');
    }

    /** @param  Builder<Project>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param  Builder<Project>  $query */
    public function scopeCurrent(Builder $query): void
    {
        $query->where('status', ProjectStatus::Current->value);
    }

    /** @param  Builder<Project>  $query */
    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', ProjectStatus::Completed->value);
    }
}
