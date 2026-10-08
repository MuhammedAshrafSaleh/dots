<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

class Tag extends Model
{
    use HasTranslations;

    public $timestamps = false;

    protected $guarded = ['id'];

    /** @var array<int, string> */
    public array $translatable = ['name'];

    /** Slug is a single non-translated string for tags. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsToMany<Post, $this> */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_tag');
    }
}
