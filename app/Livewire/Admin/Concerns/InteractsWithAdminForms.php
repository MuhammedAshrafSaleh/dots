<?php

namespace App\Livewire\Admin\Concerns;

use App\Support\Slug;

/**
 * Small helpers shared by admin form components: multi-select chip toggling
 * and per-locale slug generation. Operate on nested public properties by
 * dotted path (e.g. "form.tags", "form.slug").
 */
trait InteractsWithAdminForms
{
    /** Toggle a value in the array stored at the dotted $path. */
    public function toggleChip(string $path, int|string $value): void
    {
        /** @var array<int, int|string> $current */
        $current = (array) data_get($this, $path, []);

        $current = in_array($value, $current, true)
            ? array_values(array_filter($current, fn ($v) => $v !== $value))
            : [...$current, $value];

        data_set($this, $path, $current);
    }

    /**
     * Fill per-locale slugs at $slugPath from the translatable text at
     * $fromPath (e.g. the name field), without clobbering a manual edit.
     */
    public function fillSlugsFrom(string $fromPath, string $slugPath = 'slug'): void
    {
        foreach (['ar', 'en'] as $locale) {
            $existing = (string) data_get($this, "$slugPath.$locale", '');
            if ($existing !== '') {
                continue;
            }

            $text = (string) data_get($this, "$fromPath.$locale", '');
            if ($text !== '') {
                data_set($this, "$slugPath.$locale", Slug::make($text, $locale));
            }
        }
    }
}
