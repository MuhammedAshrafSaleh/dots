<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Governorate extends Model
{
    use HasTranslations;

    public $timestamps = false;

    protected $guarded = ['id'];

    /** @var array<int, string> */
    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @param  Builder<Governorate>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
