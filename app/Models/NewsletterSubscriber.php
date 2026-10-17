<?php

namespace App\Models;

use App\Enums\NewsletterSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property NewsletterSource $source
 * @property Carbon|null $unsubscribed_at
 */
class NewsletterSubscriber extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'source' => NewsletterSource::class,
            'unsubscribed_at' => 'datetime',
        ];
    }
}
