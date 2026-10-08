<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Cached accessor for the `settings` table. Keys are `group.key`
 * (e.g. `contact.whatsapp_number`). Values may be scalars or translatable
 * {ar, en} maps; this repository returns them raw — resolve the locale at
 * the call site. Each key is cached forever and busted on write.
 */
class Settings
{
    private const CACHE_PREFIX = 'settings:';

    public function get(string $key, mixed $default = null): mixed
    {
        [$group, $name] = $this->split($key);

        $value = Cache::rememberForever(
            self::CACHE_PREFIX.$key,
            fn () => Setting::query()->where('group', $group)->where('key', $name)->first()?->value
        );

        return $value ?? $default;
    }

    /**
     * Resolve a translatable setting for a locale (falls back to the app
     * then fallback locale). Returns $default when unset.
     */
    public function translated(string $key, ?string $locale = null, mixed $default = null): mixed
    {
        $value = $this->get($key);

        if (! is_array($value)) {
            return $value ?? $default;
        }

        $locale ??= app()->getLocale();

        return $value[$locale] ?? $value[config('app.fallback_locale')] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        [$group, $name] = $this->split($key);

        Setting::query()->updateOrCreate(
            ['group' => $group, 'key' => $name],
            ['value' => $value],
        );

        Cache::forget(self::CACHE_PREFIX.$key);
    }

    public function forget(string $key): void
    {
        Cache::forget(self::CACHE_PREFIX.$key);
    }

    /** @return array{0: string, 1: string} */
    private function split(string $key): array
    {
        [$group, $name] = array_pad(explode('.', $key, 2), 2, '');

        return [$group, $name];
    }
}
