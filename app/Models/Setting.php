<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Raw key/value store. Prefer the cached App\Services\Settings repository
 * over querying this model directly.
 */
class Setting extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'value' => 'array', // scalar wrapped or translatable {ar, en}
        ];
    }
}
