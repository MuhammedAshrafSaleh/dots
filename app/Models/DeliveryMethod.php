<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

class DeliveryMethod extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    /** @var array<int, string> */
    public array $translatable = ['name', 'description', 'note'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'price_is_from' => 'boolean',
            'free_over_amount' => 'decimal:2',
            'requires_address' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Governorates this method serves. Empty = available everywhere.
     *
     * @return BelongsToMany<Governorate, $this>
     */
    public function governorates(): BelongsToMany
    {
        return $this->belongsToMany(Governorate::class);
    }

    /** @param  Builder<DeliveryMethod>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
