<?php

namespace App\Services;

use App\Actions\UpdateProductCacheColumns;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\CacheTags;
use App\Support\Html;
use App\Support\Slug;
use Illuminate\Support\Facades\DB;

/**
 * Applies a validated import plan (IMPORT_SPEC §4–§7): upserts products /
 * variants / specs in one transaction, then attaches media after commit and
 * flushes the public caches. Data rows have already been validated by
 * ProductImportParser — this writes only the valid plan.
 */
final class ProductImportApplier
{
    private const SLOTS = [
        'datasheet' => 'datasheet',
        'ies' => 'ies',
        'ldt' => 'ldt',
        'manual' => 'guide',
    ];

    public function __construct(private readonly UpdateProductCacheColumns $cache) {}

    /**
     * @param  list<array<string, mixed>>  $plan
     * @param  array<string, array{path: string, ext: string, type: string}>|null  $mediaFiles  lowerBasename → file
     * @param  list<array{sheet: string, row: int, column: string, message: string}>  $warnings
     * @return array{created: int, updated: int, variants: int, specs: int, images: int}
     */
    public function apply(array $plan, ?array $mediaFiles, array &$warnings): array
    {
        $mediaFiles ??= [];

        // Which SKUs already exist → "update" vs "create".
        $skus = array_map(fn (array $p): string => $p['sku'], $plan);
        $existing = $skus === []
            ? []
            : Product::whereIn('sku', $skus)->pluck('sku')->flip()->all();

        // Resolve each product's media before the transaction so a NEW product
        // that ends with zero valid images can be forced to draft.
        $consumed = [];
        $resolved = [];
        foreach ($plan as $p) {
            $resolved[$p['sku']] = $this->resolveMedia($p, $mediaFiles, $consumed, $warnings);
        }
        foreach ($mediaFiles as $lower => $file) {
            if (! isset($consumed[$lower])) {
                $warnings[] = $this->warn($lower, __('admin.import_products.media_unmatched', ['name' => $lower]));
            }
        }

        $counts = ['created' => 0, 'updated' => 0, 'variants' => 0, 'specs' => 0, 'images' => 0];

        /** @var list<int> $touchedIds */
        $touchedIds = [];

        DB::transaction(function () use ($plan, $existing, $resolved, &$warnings, &$counts, &$touchedIds): void {
            foreach ($plan as $p) {
                $isNew = ! isset($existing[$p['sku']]);
                $forceDraft = $isNew && $resolved[$p['sku']]['gallery'] === [];

                $product = $this->upsertProduct($p, $isNew, $forceDraft, $warnings);

                if ($p['is_simple']) {
                    $counts['variants'] += $this->syncSimpleVariant($product, $p);
                } else {
                    $counts['variants'] += $this->syncVariants($product, $p['variants']);
                }

                $counts['specs'] += $this->replaceSpecs($product, $p['specs']);

                $this->cache->handle($product->refresh());

                $isNew ? $counts['created']++ : $counts['updated']++;
                $touchedIds[] = $product->id;
            }
        });

        // Media is attached only after the data transaction commits.
        foreach ($plan as $p) {
            $product = Product::where('sku', $p['sku'])->first();
            if ($product !== null) {
                $counts['images'] += $this->attachMedia($product, $resolved[$p['sku']]);
            }
        }

        if ($touchedIds !== []) {
            CacheTags::flush(
                CacheTags::LISTINGS,
                CacheTags::FACETS,
                CacheTags::CATEGORIES,
                CacheTags::BRANDS,
            );
        }

        return $counts;
    }

    // -------------------------------------------------------------------------
    // Products
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $p
     * @param  list<array{sheet: string, row: int, column: string, message: string}>  $warnings
     */
    private function upsertProduct(array $p, bool $isNew, bool $forceDraft, array &$warnings): Product
    {
        $product = $isNew ? new Product(['sku' => $p['sku']]) : Product::where('sku', $p['sku'])->firstOrFail();

        // name is required → always set.
        $product->setTranslations('name', $p['name']);

        // Optional translatable text: merge provided locales, keep the rest.
        foreach (['warranty', 'meta_title', 'meta_description'] as $field) {
            if ($p[$field] !== []) {
                $product->setTranslations($field, array_merge($product->getTranslations($field), $p[$field]));
            }
        }

        // overview: plain text → escaped <p> per line → purifier.
        if ($p['overview'] !== []) {
            $clean = [];
            foreach ($p['overview'] as $locale => $text) {
                $clean[$locale] = $this->overviewHtml($text);
            }
            $product->setTranslations('overview', array_merge($product->getTranslations('overview'), $clean));
        }

        $product->brand_id = $p['brand_id'];
        $product->category_id = $p['category_id'];

        if ($p['price_on_request'] !== null) {
            $product->price_on_request = $p['price_on_request'];
        } elseif ($isNew) {
            $product->price_on_request = false;
        }

        foreach ($p['flags'] as $flag => $value) {
            if ($value !== null) {
                $product->{$flag} = $value;
            } elseif ($isNew) {
                $product->{$flag} = false;
            }
        }

        // Status: forced draft wins, else sheet value, else draft on create / keep on update.
        if ($forceDraft) {
            $product->status = ProductStatus::Draft;
            $warnings[] = $this->warn($p['sku'], __('admin.import_products.media_forced_draft', ['sku' => $p['sku']]));
        } elseif ($p['status'] !== null) {
            $product->status = $p['status'];
        } elseif ($isNew) {
            $product->status = ProductStatus::Draft;
        }

        if ($product->status === ProductStatus::Active && $product->published_at === null) {
            $product->published_at = now();
        }

        if ($isNew) {
            $product->setTranslations('slug', $this->uniqueSlugs($p['name']));
        }

        $product->save();

        return $product;
    }

    private function overviewHtml(string $text): string
    {
        $html = collect(preg_split('/\r\n|\r|\n/', $text) ?: [])
            ->map(fn (string $line): string => '<p>'.e($line).'</p>')
            ->implode('');

        return Html::clean($html);
    }

    /**
     * @param  array<string, string>  $name
     * @return array<string, string>
     */
    private function uniqueSlugs(array $name): array
    {
        $slugs = [];
        foreach (['ar', 'en'] as $locale) {
            $base = Slug::make($name[$locale] ?? '', $locale) ?: 'product';
            $slug = $base;
            $n = 2;
            while (Product::where('slug_'.$locale, $slug)->exists()) {
                $slug = $base.'-'.$n++;
            }
            $slugs[$locale] = $slug;
        }

        return $slugs;
    }

    // -------------------------------------------------------------------------
    // Variants
    // -------------------------------------------------------------------------

    /**
     * Simple product → exactly one default variant whose SKU = the product SKU.
     * Any other variants (e.g. a product that used to be a variant product)
     * are deactivated, never deleted.
     *
     * @param  array<string, mixed>  $p
     */
    private function syncSimpleVariant(Product $product, array $p): int
    {
        /** @var ProductVariant $variant */
        $variant = $product->variants()->where('sku', $product->sku)->first() ?? $product->variants()->make(['sku' => $product->sku]);
        $existed = $variant->exists;

        if ($p['price'] !== null) {
            $variant->price = $p['price'];
        } elseif (! $existed) {
            $variant->price = null;
        }
        if ($p['compare_at_price'] !== null) {
            $variant->compare_at_price = $p['compare_at_price'];
        } elseif (! $existed) {
            $variant->compare_at_price = null;
        }
        if ($p['stock_qty'] !== null) {
            $variant->stock_qty = $p['stock_qty'];
        } elseif (! $existed) {
            $variant->stock_qty = 0;
        }
        $variant->is_default = true;
        $variant->is_active = true;
        $variant->sort_order = 0;
        $product->variants()->save($variant);

        $product->variants()->where('sku', '!=', $product->sku)->update(['is_active' => false, 'is_default' => false]);

        return 1;
    }

    /**
     * Variant product → upsert each variant by SKU, sync attribute values,
     * ensure exactly one default, and deactivate variants missing from the file.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    private function syncVariants(Product $product, array $rows): int
    {
        $keptSkus = [];
        $order = 0;
        $hasDefault = false;

        foreach ($rows as $vr) {
            /** @var ProductVariant $variant */
            $variant = $product->variants()->where('sku', $vr['variant_sku'])->first() ?? $product->variants()->make(['sku' => $vr['variant_sku']]);
            $existed = $variant->exists;

            if ($vr['price'] !== null) {
                $variant->price = $vr['price'];
            } elseif (! $existed) {
                $variant->price = null;
            }
            if ($vr['compare_at_price'] !== null) {
                $variant->compare_at_price = $vr['compare_at_price'];
            } elseif (! $existed) {
                $variant->compare_at_price = null;
            }
            if ($vr['stock_qty'] !== null) {
                $variant->stock_qty = $vr['stock_qty'];
            } elseif (! $existed) {
                $variant->stock_qty = 0;
            }
            $variant->is_active = true;
            $variant->is_default = $vr['is_default'] === true;
            $variant->sort_order = $order++;
            $product->variants()->save($variant);
            $variant->attributeValues()->sync($vr['value_ids']);

            $hasDefault = $hasDefault || $variant->is_default;
            $keptSkus[] = $vr['variant_sku'];
        }

        // Exactly one default — the first kept variant wins when none flagged.
        if (! $hasDefault && $keptSkus !== []) {
            $product->variants()->where('sku', $keptSkus[0])->update(['is_default' => true]);
        }

        $product->variants()
            ->whereNotIn('sku', $keptSkus ?: [''])
            ->update(['is_active' => false, 'is_default' => false]);

        return count($keptSkus);
    }

    /**
     * Specs replace-all for the product (order = row order). No rows → untouched.
     *
     * @param  list<array<string, mixed>>  $specs
     */
    private function replaceSpecs(Product $product, array $specs): int
    {
        if ($specs === []) {
            return 0;
        }

        $product->specs()->delete();
        $order = 0;
        foreach ($specs as $spec) {
            $product->specs()->create([
                'name' => $spec['name'],
                'value' => $spec['value'],
                'sort_order' => $order++,
            ]);
        }

        return count($specs);
    }

    // -------------------------------------------------------------------------
    // Media
    // -------------------------------------------------------------------------

    /**
     * Resolve a product's gallery images + file slots against the extracted ZIP.
     * Missing listed files become warnings; the product still imports.
     *
     * @param  array<string, mixed>  $p
     * @param  array<string, array{path: string, ext: string, type: string}>  $mediaFiles
     * @param  array<string, true>  $consumed
     * @param  list<array{sheet: string, row: int, column: string, message: string}>  $warnings
     * @return array{gallery: list<array{path: string, name: string}>, slots: array<string, array{path: string, name: string}>}
     */
    private function resolveMedia(array $p, array $mediaFiles, array &$consumed, array &$warnings): array
    {
        $gallery = [];
        $sku = $p['sku'];

        if ($p['image_files'] !== []) {
            // Explicit list, in order.
            foreach ($p['image_files'] as $name) {
                $lower = mb_strtolower($name);
                $file = $mediaFiles[$lower] ?? null;
                if ($file === null) {
                    $warnings[] = $this->warn($sku, __('admin.import_products.media_listed_missing', ['name' => $name]));
                } elseif ($file['type'] !== 'image') {
                    $warnings[] = $this->warn($sku, __('admin.import_products.media_listed_not_image', ['name' => $name]));
                } else {
                    $gallery[] = ['path' => $file['path'], 'name' => $name];
                    $consumed[$lower] = true;
                }
            }
        } elseif ($mediaFiles !== []) {
            // Auto-match by SKU: SKU.ext, SKU-2.ext, SKU-3.ext …
            foreach ($mediaFiles as $lower => $file) {
                if ($file['type'] !== 'image') {
                    continue;
                }
                $stem = pathinfo($lower, PATHINFO_FILENAME);
                if ($stem === mb_strtolower($sku) || preg_match('/^'.preg_quote(mb_strtolower($sku), '/').'-(\d+)$/', $stem) === 1) {
                    $gallery[] = ['path' => $file['path'], 'name' => basename($file['path']), 'stem' => $stem];
                    $consumed[$lower] = true;
                }
            }
            usort($gallery, fn (array $a, array $b): int => $this->galleryRank($a['stem'], $sku) <=> $this->galleryRank($b['stem'], $sku));
            $gallery = array_map(fn (array $g): array => ['path' => $g['path'], 'name' => $g['name']], $gallery);
        }

        // File slots by suffix — always auto-matched by SKU.
        $slots = [];
        foreach (self::SLOTS as $suffix => $slot) {
            foreach ($mediaFiles as $lower => $file) {
                if ($file['type'] !== 'file') {
                    continue;
                }
                $stem = pathinfo($lower, PATHINFO_FILENAME);
                if ($stem === mb_strtolower($sku.'-'.$suffix)) {
                    $slots[$slot] = ['path' => $file['path'], 'name' => basename($file['path'])];
                    $consumed[$lower] = true;
                    break;
                }
            }
        }

        return ['gallery' => $gallery, 'slots' => $slots];
    }

    private function galleryRank(string $stem, string $sku): int
    {
        $sku = mb_strtolower($sku);
        if ($stem === $sku) {
            return 0;
        }
        if (preg_match('/-(\d+)$/', $stem, $m) === 1) {
            return (int) $m[1];
        }

        return PHP_INT_MAX;
    }

    /**
     * @param  array{gallery: list<array{path: string, name: string}>, slots: array<string, array{path: string, name: string}>}  $resolved
     */
    private function attachMedia(Product $product, array $resolved): int
    {
        $count = 0;

        if ($resolved['gallery'] !== []) {
            $product->clearMediaCollection('gallery');
            foreach ($resolved['gallery'] as $image) {
                if (is_file($image['path'])) {
                    $product->addMedia($image['path'])
                        ->usingFileName($image['name'])
                        ->toMediaCollection('gallery');
                    $count++;
                }
            }
        }

        foreach ($resolved['slots'] as $slot => $file) {
            if (! is_file($file['path'])) {
                continue;
            }
            $product->getMedia('files')
                ->filter(fn ($m) => $m->getCustomProperty('slot') === $slot)
                ->each->delete();
            $product->addMedia($file['path'])
                ->usingFileName($file['name'])
                ->withCustomProperties(['slot' => $slot])
                ->toMediaCollection('files');
            $count++;
        }

        return $count;
    }

    /** @return array{sheet: string, row: int, column: string, message: string} */
    private function warn(string $column, string $message): array
    {
        return ['sheet' => __('admin.import_products.media_sheet'), 'row' => 0, 'column' => $column, 'message' => $message];
    }
}
