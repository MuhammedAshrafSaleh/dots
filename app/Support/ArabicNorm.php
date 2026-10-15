<?php

namespace App\Support;

/**
 * Arabic text normalisation for search indexing and query matching.
 *
 * Applies identical transforms to both the indexed search_text and the
 * user's query so that spelling variants (alef, yaa, taa marbuta,
 * diacritics) resolve to the same canonical form.
 */
class ArabicNorm
{
    public static function normalize(string $text): string
    {
        // Strip harakat (U+064B–U+065F) and tatweel (U+0640)
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0640}]/u', '', $text) ?? $text;
        // Alef with hamza/madda variants → bare alef (ا)
        $text = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $text);
        // Alef maqsura (ى) → yaa with dots (ي)
        $text = str_replace('ى', 'ي', $text);
        // Taa marbuta (ة) → haa (ه)
        $text = str_replace('ة', 'ه', $text);

        return $text;
    }

    /**
     * Normalise and sanitise a user-supplied query string for use in a
     * MySQL FULLTEXT MATCH…AGAINST(… IN BOOLEAN MODE) expression.
     * Strips boolean operator characters that would cause a parse error.
     */
    public static function sanitizeQuery(string $term): string
    {
        $term = self::normalize($term);

        return preg_replace('/[+\-><()~*"@]+/', ' ', $term) ?? $term;
    }
}
