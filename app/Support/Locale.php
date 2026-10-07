<?php

namespace App\Support;

use Illuminate\Support\Facades\Request;

/**
 * Central helper for the site's two locales (ar default, en).
 * Keeps locale metadata and URL rewriting in one place so the
 * language switch and the SetLocale middleware share one source of truth.
 */
class Locale
{
    /** Supported locales, default first. */
    public const SUPPORTED = ['ar', 'en'];

    public const DEFAULT = 'ar';

    /** Text direction per locale. */
    public static function dir(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'ar' ? 'rtl' : 'ltr';
    }

    public static function isRtl(?string $locale = null): bool
    {
        return self::dir($locale) === 'rtl';
    }

    public static function isSupported(?string $locale): bool
    {
        return in_array($locale, self::SUPPORTED, true);
    }

    /** The "other" locale, used by the language switch toggle. */
    public static function other(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'ar' ? 'en' : 'ar';
    }

    /** BCP-47 tag for <html lang> / hreflang. */
    public static function tag(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'ar' ? 'ar-EG' : 'en';
    }

    /**
     * Rewrite a path so its leading locale segment becomes $target,
     * preserving the rest of the path and the query string. Used by the
     * language switch to keep the user on the equivalent URL. Model pages
     * with per-locale slugs pass an explicit counterpart $path.
     */
    public static function switchUrl(string $target, ?string $path = null): string
    {
        $target = self::isSupported($target) ? $target : self::DEFAULT;

        $path ??= Request::getRequestUri(); // path + query, no host
        [$pathOnly, $query] = array_pad(explode('?', $path, 2), 2, null);

        $segments = explode('/', trim($pathOnly, '/'));
        if (isset($segments[0]) && self::isSupported($segments[0])) {
            $segments[0] = $target;
        } else {
            array_unshift($segments, $target);
        }

        $rebuilt = '/'.implode('/', array_filter($segments, fn ($s) => $s !== ''));

        return $query !== null && $query !== '' ? $rebuilt.'?'.$query : $rebuilt;
    }
}
