<?php

namespace App\Models\Concerns;

use App\Support\Locale;
use Illuminate\Database\Eloquent\Model;

/**
 * Route-model binding by the current locale's slug.
 *
 * Slugs are stored as a translatable JSON column (e.g. `slug`) with
 * generated STORED companion columns `slug_ar` / `slug_en` (UNIQUE) per the
 * Phase 0 slug decision. Binding resolves against the generated column for
 * the active locale so it uses the index. Falls back to the other locale so
 * a shared/legacy link still resolves.
 *
 * Models using this trait should set `getRouteKeyName()` via `$slugKey`
 * (defaults to `slug`) and expose matching `{slugKey}_{locale}` columns.
 */
trait HasTranslatableSlug
{
    public function getRouteKeyName(): string
    {
        return $this->slugKey();
    }

    protected function slugKey(): string
    {
        return property_exists($this, 'slugKey') ? $this->slugKey : 'slug';
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $key = $field ?: $this->slugKey();

        // When binding on the slug, target the locale-specific generated column.
        if ($key === $this->slugKey()) {
            $locale = Locale::isSupported(app()->getLocale()) ? app()->getLocale() : Locale::DEFAULT;
            $other = Locale::other($locale);

            return $this->newQuery()
                ->where("{$key}_{$locale}", $value)
                ->orWhere("{$key}_{$other}", $value)
                ->first();
        }

        return $this->newQuery()->where($key, $value)->first();
    }
}
