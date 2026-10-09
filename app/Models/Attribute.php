<?php

namespace App\Models;

use App\Enums\AttributeDisplay;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * @property AttributeDisplay $display
 * @property bool $is_filterable
 * @property bool $is_variant_axis
 */
class Attribute extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    /** @var array<int, string> */
    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'display' => AttributeDisplay::class,
            'is_filterable' => 'boolean',
            'is_variant_axis' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return HasMany<AttributeValue, $this> */
    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('sort_order');
    }
}
