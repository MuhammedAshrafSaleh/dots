<?php

namespace App\Support;

use Mews\Purifier\Facades\Purifier;

/**
 * Sanitiser for admin rich-text (ARich). Strips everything outside the
 * editor's allowed block set (Decisions Q6) using the `dots_rich` HTMLPurifier
 * profile. Call on save for every rich-text field before it hits the DB.
 */
final class Html
{
    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        return (string) Purifier::clean($html, 'dots_rich');
    }

    /**
     * Sanitise a translatable {ar, en} value.
     *
     * @param  array<string, string|null>  $value
     * @return array<string, string>
     */
    public static function cleanTranslations(array $value): array
    {
        return array_map(fn ($html) => self::clean($html), $value);
    }
}
