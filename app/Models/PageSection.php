<?php

namespace App\Models;

use App\Models\Concerns\HasImageConversions;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * An editable content block. Its `content` JSON follows the fixed schema in
 * config/page_sections.php for this row's `page.key`; the accessor below
 * returns typed values with sensible defaults so views never guess shapes.
 *
 * @property string $page
 * @property string $key
 * @property array<string, mixed>|null $content
 * @property bool $is_active
 * @property int $sort_order
 */
class PageSection extends Model implements HasMedia
{
    use HasImageConversions;
    use InteractsWithMedia;

    /** Scalar field types whose stored value is a translatable {ar, en} map. */
    private const TRANSLATABLE_TYPES = ['text'];

    /** Field types that live on the media collection, not in `content`. */
    private const MEDIA_TYPES = ['media', 'video', 'media_many'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('section');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Skip image conversions for non-image MIME types (e.g. video slots).
        if ($media !== null && ! str_starts_with($media->mime_type ?? '', 'image/')) {
            return;
        }
        $this->registerResponsiveImageConversions();
    }

    /** @return array<int, string> */
    public function imageConversionCollections(): array
    {
        return ['section'];
    }

    public function schemaKey(): string
    {
        return "{$this->page}.{$this->key}";
    }

    /**
     * The field schema for a given page.key (empty if unknown).
     *
     * @return array<string, mixed>
     */
    public static function schemaFor(string $page, string $key): array
    {
        return config("page_sections.{$page}.{$key}", []);
    }

    /** @return array<string, mixed> */
    public function schema(): array
    {
        return self::schemaFor($this->page, $this->key);
    }

    /**
     * Resolve a field to a typed value with defaults:
     * translatable text → the string for $locale (falling back to the app
     * locale), list → an array of resolved item maps, link/icon → the raw
     * string. Media fields return null (fetch them via the media collection).
     */
    public function field(string $name, ?string $locale = null): mixed
    {
        $spec = $this->schema()[$name] ?? null;

        if ($spec === null) {
            return null;
        }

        $locale ??= app()->getLocale();
        $raw = is_array($this->content) ? ($this->content[$name] ?? null) : null;

        if (is_array($spec) && ($spec['type'] ?? null) === 'list') {
            return $this->resolveList($spec, is_array($raw) ? $raw : [], $locale);
        }

        if (in_array($spec, self::MEDIA_TYPES, true) || (is_array($spec) && in_array($spec['type'] ?? null, self::MEDIA_TYPES, true))) {
            return null;
        }

        if (in_array($spec, self::TRANSLATABLE_TYPES, true)) {
            return $this->resolveTranslatable(is_array($raw) ? $raw : [], $locale);
        }

        return is_string($raw) ? $raw : '';
    }

    /**
     * Validate a content payload against the schema. Returns a list of human
     * error strings; empty = valid.
     *
     * @param  array<string, mixed>  $content
     * @return array<int, string>
     */
    public static function validateContent(string $page, string $key, array $content): array
    {
        $schema = self::schemaFor($page, $key);

        if ($schema === []) {
            return ["Unknown section {$page}.{$key}"];
        }

        $errors = [];

        foreach (array_keys($content) as $field) {
            if (! array_key_exists($field, $schema)) {
                $errors[] = "Unknown field '{$field}'";
            }
        }

        foreach ($schema as $field => $spec) {
            if (! array_key_exists($field, $content)) {
                continue; // absent = use default
            }

            $value = $content[$field];

            if (is_array($spec) && ($spec['type'] ?? null) === 'list') {
                if (! is_array($value)) {
                    $errors[] = "Field '{$field}' must be a list";
                } elseif (count($value) > $spec['count']) {
                    $errors[] = "Field '{$field}' allows at most {$spec['count']} items";
                }

                continue;
            }

            if (in_array($spec, self::TRANSLATABLE_TYPES, true) && ! is_array($value)) {
                $errors[] = "Field '{$field}' must be a translatable {ar, en} map";
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $spec
     * @param  array<int, mixed>  $raw
     * @return array<int, array<string, mixed>>
     */
    private function resolveList(array $spec, array $raw, string $locale): array
    {
        /** @var array<string, string> $fields */
        $fields = $spec['fields'];
        $out = [];

        foreach ($raw as $item) {
            $item = is_array($item) ? $item : [];
            $resolved = [];

            foreach ($fields as $field => $type) {
                $cell = $item[$field] ?? null;
                $resolved[$field] = in_array($type, self::TRANSLATABLE_TYPES, true)
                    ? $this->resolveTranslatable(is_array($cell) ? $cell : [], $locale)
                    : (is_string($cell) ? $cell : '');
            }

            $out[] = $resolved;
        }

        return $out;
    }

    /** @param  array<string, mixed>  $value */
    private function resolveTranslatable(array $value, string $locale): string
    {
        $resolved = $value[$locale] ?? $value[config('app.fallback_locale')] ?? '';

        return is_string($resolved) ? $resolved : '';
    }
}
