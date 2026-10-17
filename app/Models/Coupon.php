<?php

namespace App\Models;

use App\Enums\CouponType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property CouponType $type
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 */
class Coupon extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'decimal:2',
            'min_order_total' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'once_per_customer' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<CouponRedemption, $this> */
    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    /**
     * Active and within its usage window / limit.
     *
     * @param  Builder<Coupon>  $query
     */
    public function scopeValid(Builder $query): void
    {
        $now = Carbon::now();

        $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->where(fn (Builder $q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'));
    }
}
