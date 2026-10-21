<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per bulk-import "Apply" run: who ran it, when, the file name,
 * the applied counts, and a link to the generated errors/warnings report.
 *
 * @property string $status
 */
class ImportLog extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'created_count' => 'integer',
            'updated_count' => 'integer',
            'variants_count' => 'integer',
            'specs_count' => 'integer',
            'images_count' => 'integer',
            'errors_count' => 'integer',
            'warnings_count' => 'integer',
            'finished_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
