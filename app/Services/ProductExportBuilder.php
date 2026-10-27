<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Attribute;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Produces a product data export in the same sheets/keys as the import template.
 *
 * - images column is always empty (re-import never touches media)
 * - Values starting with = + - @ are escaped with a leading single quote
 * - Round-trip safe: export → re-import makes zero changes
 */
final class ProductExportBuilder
{
    public function build(): Spreadsheet
    {
        $templateBuilder = new ProductTemplateBuilder;
        $spreadsheet = $templateBuilder->build();

        $axisAttrs = Attribute::where('is_variant_axis', true)->orderBy('sort_order')->with('values')->get();

        $this->fillProducts($spreadsheet, $axisAttrs);
        $this->fillVariants($spreadsheet, $axisAttrs);
        $this->fillSpecs($spreadsheet);

        $spreadsheet->setActiveSheetIndex(1); // المنتجات

        return $spreadsheet;
    }

    // ------------------------------------------------------------------

    /** @param Collection<int, Attribute> $axisAttrs */
    private function fillProducts(Spreadsheet $spreadsheet, Collection $axisAttrs): void
    {
        $sheet = $spreadsheet->getSheetByName('المنتجات');
        if (! $sheet) {
            return;
        }

        // Read key row to map key → column letter
        $keyMap = $this->readKeyRow($sheet);

        $products = Product::with([
            'brand',
            'category.parent.parent',
            'variants',
        ])->orderBy('id')->lazy();

        $row = 3;
        foreach ($products as $product) {
            // Product-level price/stock belong to a simple product only (one
            // variant whose SKU = the product SKU). Variant products leave them
            // empty so a round-trip re-import is not flagged as a conflict.
            $variants = $product->variants;
            $isSimple = $variants->count() === 1 && $variants->first()?->sku === $product->sku;
            $defaultVariant = $isSimple ? $variants->first() : null;
            $boolStatus = fn (bool $b): string => $b ? 'نعم' : 'لا';

            $data = [
                'sku' => $product->sku,
                'name_ar' => $product->getTranslation('name', 'ar'),
                'name_en' => $product->getTranslation('name', 'en'),
                'brand' => $product->brand->name,
                'category' => $this->categoryPath($product),
                'status' => match ($product->status) {
                    ProductStatus::Active => 'منشور',
                    ProductStatus::Draft => 'مسودة',
                    ProductStatus::Archived => 'مؤرشف',
                },
                'price' => $defaultVariant !== null ? ($defaultVariant->price ?? '') : '',
                'compare_at_price' => $defaultVariant !== null ? ($defaultVariant->compare_at_price ?? '') : '',
                'stock_qty' => $defaultVariant !== null ? $defaultVariant->stock_qty : '',
                'price_on_request' => $boolStatus($product->price_on_request),
                'overview_ar' => $product->getTranslation('overview', 'ar'),
                'overview_en' => $product->getTranslation('overview', 'en'),
                'warranty_ar' => $product->getTranslation('warranty', 'ar'),
                'warranty_en' => $product->getTranslation('warranty', 'en'),
                'is_new' => $boolStatus($product->is_new),
                'is_best_seller' => $boolStatus($product->is_best_seller),
                'is_smart_pick' => $boolStatus($product->is_smart_pick),
                'is_dimmable' => $boolStatus($product->is_dimmable),
                'ships_24h' => $boolStatus($product->ships_24h),
                'images' => '', // always empty on export
                'meta_title_ar' => $product->getTranslation('meta_title', 'ar'),
                'meta_title_en' => $product->getTranslation('meta_title', 'en'),
                'meta_description_ar' => $product->getTranslation('meta_description', 'ar'),
                'meta_description_en' => $product->getTranslation('meta_description', 'en'),
            ];

            foreach ($data as $key => $value) {
                if (! isset($keyMap[$key])) {
                    continue;
                }
                $sheet->setCellValueExplicit(
                    $keyMap[$key].$row,
                    $this->escape((string) $value),
                    DataType::TYPE_STRING2,
                );
            }
            $row++;
        }
    }

    /** @param Collection<int, Attribute> $axisAttrs */
    private function fillVariants(Spreadsheet $spreadsheet, Collection $axisAttrs): void
    {
        $sheet = $spreadsheet->getSheetByName('الاختيارات');
        if (! $sheet) {
            return;
        }

        $keyMap = $this->readKeyRow($sheet);

        // Only variant products (those with multiple or explicitly tracked variants)
        $products = Product::with([
            'variants.attributeValues.attribute',
        ])->orderBy('id')->lazy();

        $row = 3;
        foreach ($products as $product) {
            // Simple products = one variant whose sku matches the product sku
            $variants = $product->variants;
            $isSimple = $variants->count() === 1 && $variants->first()?->sku === $product->sku;
            if ($isSimple) {
                continue;
            }

            foreach ($variants as $variant) {
                $boolVal = fn (bool $b): string => $b ? 'نعم' : 'لا';

                $data = [
                    'product_sku' => $product->sku,
                    'variant_sku' => $variant->sku,
                    'price' => $variant->price ?? '',
                    'compare_at_price' => $variant->compare_at_price ?? '',
                    'stock_qty' => $variant->stock_qty ?? '',
                    'is_default' => $boolVal((bool) $variant->is_default),
                ];

                // attr:<code> columns
                $attrValues = $variant->attributeValues->keyBy(fn ($av) => $av->attribute?->code);
                foreach ($axisAttrs as $attr) {
                    $av = $attrValues->get($attr->code);
                    $label = $av ? ($av->getTranslation('label', 'ar') ?: $av->value) : '';
                    $data['attr:'.$attr->code] = $label;
                }

                foreach ($data as $key => $value) {
                    if (! isset($keyMap[$key])) {
                        continue;
                    }
                    $sheet->setCellValueExplicit(
                        $keyMap[$key].$row,
                        $this->escape((string) $value),
                        DataType::TYPE_STRING2,
                    );
                }
                $row++;
            }
        }
    }

    private function fillSpecs(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->getSheetByName('المواصفات');
        if (! $sheet) {
            return;
        }

        $keyMap = $this->readKeyRow($sheet);

        $products = Product::with(['specs' => fn ($q) => $q->orderBy('sort_order')])->orderBy('id')->lazy();

        $row = 3;
        foreach ($products as $product) {
            foreach ($product->specs as $spec) {
                $data = [
                    'product_sku' => $product->sku,
                    'name_ar' => $spec->getTranslation('name', 'ar'),
                    'name_en' => $spec->getTranslation('name', 'en'),
                    'value_ar' => $spec->getTranslation('value', 'ar'),
                    'value_en' => $spec->getTranslation('value', 'en'),
                ];

                foreach ($data as $key => $value) {
                    if (! isset($keyMap[$key])) {
                        continue;
                    }
                    $sheet->setCellValueExplicit(
                        $keyMap[$key].$row,
                        $this->escape((string) $value),
                        DataType::TYPE_STRING2,
                    );
                }
                $row++;
            }
        }
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Read row 2 of a sheet and return a map of key → Excel column letter.
     *
     * @return array<string, string>
     */
    private function readKeyRow(Worksheet $sheet): array
    {
        $map = [];
        $highest = $sheet->getHighestColumn();
        $highestN = Coordinate::columnIndexFromString($highest);

        for ($i = 1; $i <= $highestN; $i++) {
            $col = Coordinate::stringFromColumnIndex($i);
            $key = (string) $sheet->getCell($col.'2')->getValue();
            if ($key !== '') {
                $map[$key] = $col;
            }
        }

        return $map;
    }

    /** Escape formula-injection characters. */
    private function escape(string $value): string
    {
        if ($value === '') {
            return '';
        }
        if (in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'".$value;
        }

        return $value;
    }

    private function categoryPath(Product $product): string
    {
        $cat = $product->category;
        if (! $cat) {
            return '';
        }
        $l3 = $cat->getTranslation('name', 'ar');
        $l2 = $cat->parent?->getTranslation('name', 'ar') ?? '';
        $l1 = $cat->parent?->parent?->getTranslation('name', 'ar') ?? '';

        $parts = array_filter([$l1, $l2, $l3], fn (string $p) => $p !== '');

        return implode(' > ', $parts);
    }
}
