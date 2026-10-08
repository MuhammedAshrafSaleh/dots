<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Per-locale slugger. English uses Str::slug; Arabic is Unicode-preserving so
 * AR URLs keep Arabic script (per the Phase 0 slug decision). Callers handle
 * `-2`/`-3` collision suffixes.
 */
final class Slug
{
    /**
     * @param  array<string, string>  $translations  e.g. ['ar' => '...', 'en' => '...']
     * @return array<string, string>
     */
    public static function fromTranslations(array $translations): array
    {
        $slugs = [];

        foreach ($translations as $locale => $text) {
            $slugs[$locale] = self::make($text, $locale);
        }

        return $slugs;
    }

    public static function make(string $text, string $locale): string
    {
        if ($locale === 'ar') {
            // Keep Arabic letters and digits; drop other punctuation; spaces → "-".
            $text = preg_replace('/[^\p{Arabic}\p{N}\s-]+/u', '', $text) ?? '';
            $text = preg_replace('/[\s-]+/u', '-', trim($text)) ?? '';

            return trim($text, '-');
        }

        return Str::slug($text);
    }
}
