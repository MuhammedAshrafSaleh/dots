<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Translatable\HasTranslations;

class Faq extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    /** @var array<int, string> */
    public array $translatable = ['question', 'answer'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function faqable(): MorphTo
    {
        return $this->morphTo();
    }
}
