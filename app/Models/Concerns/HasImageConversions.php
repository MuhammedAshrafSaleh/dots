<?php

namespace App\Models\Concerns;

use App\Support\ResponsiveImage;

/**
 * Registers the standard responsive WebP conversions on a model's image
 * collections. Conversions are queued by default (see media-library config);
 * with QUEUE_CONNECTION=sync they run inline.
 *
 * A model using this trait must call $this->registerResponsiveImageConversions()
 * from registerMediaConversions() and declare which collections hold images via
 * imageConversionCollections().
 */
trait HasImageConversions
{
    /**
     * Collections that hold raster images and should receive conversions.
     * Non-image collections (files, catalogue PDFs) must be excluded.
     *
     * @return array<int, string>
     */
    abstract public function imageConversionCollections(): array;

    /**
     * Register the WebP conversions, scoped to the image collections only so
     * PDFs and other file collections are never processed.
     */
    protected function registerResponsiveImageConversions(): void
    {
        $collections = $this->imageConversionCollections();

        if ($collections === []) {
            return;
        }

        foreach (ResponsiveImage::SIZES as $name => $width) {
            $this->addMediaConversion($name)
                ->performOnCollections(...$collections)
                ->format('webp')
                ->width($width);
        }
    }
}
