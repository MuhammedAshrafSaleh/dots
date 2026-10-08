<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasTranslatableSlug;
    use HasTranslations;

    protected $guarded = ['id'];

    /** @var array<int, string> */
    public array $translatable = ['title', 'slug', 'lede', 'body', 'meta_title', 'meta_description'];

    protected function casts(): array
    {
        return [
            'show_in_footer' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @param  Builder<Page>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
