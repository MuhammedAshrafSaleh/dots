<?php

namespace Database\Seeders\Concerns;

use Spatie\MediaLibrary\HasMedia;

/**
 * Attaches the exact design photos (design/website/assets/images/ph{n}.jpg,
 * logo.png, heroVid.mp4) so seeded records mirror the design. Files are
 * preserved in place (copied into the media disk, original left untouched).
 */
trait SeedsDesignMedia
{
    protected function imagesPath(): string
    {
        return base_path('design/website/assets/images');
    }

    /** @param  array<string, mixed>  $customProperties */
    protected function attachPhoto(HasMedia $model, int $photoId, string $collection, array $customProperties = []): void
    {
        $path = $this->imagesPath()."/ph{$photoId}.jpg";

        if (! is_file($path)) {
            return;
        }

        $model->addMedia($path)
            ->preservingOriginal()
            ->withCustomProperties($customProperties)
            ->toMediaCollection($collection);
    }

    protected function attachFile(HasMedia $model, string $filename, string $collection): void
    {
        $path = $this->imagesPath()."/{$filename}";

        if (! is_file($path)) {
            return;
        }

        $model->addMedia($path)->preservingOriginal()->toMediaCollection($collection);
    }
}
