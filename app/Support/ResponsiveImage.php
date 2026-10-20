<?php

namespace App\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Single source of truth for the responsive WebP conversion sizes and the
 * srcset/src they produce. Used by App\Models\Concerns\HasImageConversions to
 * register conversions and by the <x-image-frame> component to render them.
 */
final class ResponsiveImage
{
    /**
     * Conversion name => intended width in CSS pixels, smallest first.
     *
     * @var array<string, int>
     */
    public const SIZES = [
        'thumb' => 160,
        'card' => 640,
        'large' => 1280,
        'hero' => 1920,
    ];

    /**
     * Build a srcset string from every generated conversion on the media item.
     */
    public static function srcset(Media $media): string
    {
        $parts = [];

        foreach (self::SIZES as $name => $width) {
            if ($media->hasGeneratedConversion($name)) {
                $parts[] = $media->getUrl($name).' '.$width.'w';
            }
        }

        return implode(', ', $parts);
    }

    /**
     * A sensible default src for browsers that ignore srcset: the largest
     * generated conversion at or below the preferred width, else the original.
     */
    public static function fallbackUrl(Media $media, string $prefer = 'card'): string
    {
        if ($media->hasGeneratedConversion($prefer)) {
            return $media->getUrl($prefer);
        }

        foreach (array_reverse(self::SIZES, true) as $name => $width) {
            if ($media->hasGeneratedConversion($name)) {
                return $media->getUrl($name);
            }
        }

        return $media->getUrl();
    }
}
