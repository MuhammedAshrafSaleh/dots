<?php

namespace App\Models;

use App\Enums\PaymentMethodCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class PaymentMethod extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    /** @var array<int, string> */
    public array $translatable = ['name', 'description', 'instructions'];

    protected function casts(): array
    {
        return [
            'code' => PaymentMethodCode::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @param  Builder<PaymentMethod>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
