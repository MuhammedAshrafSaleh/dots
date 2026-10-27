<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Parses an import xlsx and validates all rules (dry run — no writes).
 * Columns are mapped by the hidden row-2 system key, never by position.
 *
 * Returns an array with keys:
 *   errors   – [['sheet', 'row', 'column', 'message'], ...]
 *   warnings – same shape
 *   counts   – ['create', 'update', 'variants', 'specs', 'images']
 *   hasValidRows – bool
 */
final class ProductImportParser
{
    // Arabic error messages --------------------------------------------------

    private const ERR_EXAMPLE_SKU = 'هذا الكود مثالي (EXAMPLE-)، احذف الصفوف المثالية قبل الرفع.';

    private const ERR_REQUIRED = 'هذا الحقل مطلوب.';

    private const ERR_BRAND_NOT_FOUND = 'العلامة التجارية غير موجودة — أضفها من لوحة التحكم ثم نزّل القالب من جديد.';

    private const ERR_CATEGORY_NOT_FOUND = 'القسم غير موجود — أضفه من لوحة التحكم ثم نزّل القالب من جديد.';

    private const ERR_ATTR_VALUE_NOT_FOUND = 'قيمة الخاصية غير موجودة — أضفها من لوحة التحكم ثم نزّل القالب من جديد.';

    private const ERR_INVALID_STATUS = 'الحالة يجب أن تكون: منشور أو مسودة أو مؤرشف.';

    private const ERR_PRICE_NOT_NUMERIC = 'السعر يجب أن يكون رقمًا موجبًا.';

    private const ERR_COMPARE_NOT_NUMERIC = 'السعر قبل الخصم يجب أن يكون رقمًا موجبًا.';

    private const ERR_COMPARE_NOT_GREATER = 'السعر قبل الخصم يجب أن يكون أعلى من السعر الأصلي.';

    private const ERR_STOCK_INVALID = 'الكمية يجب أن تكون عددًا صحيحًا غير سالب (0 أو أكثر).';

    private const ERR_PRICE_REQUIRED = 'السعر مطلوب (أو اضبط "السعر عند الطلب" = نعم).';

    private const ERR_VARIANT_PRODUCT_HAS_PRICE = 'منتج باختيارات لا يجوز أن يحتوي على سعر أو كمية في ورقة المنتجات.';

    private const ERR_PARENT_SKU_NOT_FOUND = 'كود المنتج الأب غير موجود في ورقة المنتجات.';

    private const ERR_PARENT_SKU_ERRORED = 'صف المنتج الأب يحتوي على خطأ ومن ثمّ تم تجاهل هذا الصف.';

    private const ERR_DUPLICATE_SKU = 'كود المنتج مكرر في الملف.';

    private const ERR_VARIANT_SKU_SAME_AS_PRODUCT = 'كود الاختيار لا يمكن أن يكون مطابقًا لكود المنتج الأب.';

    private const STATUS_MAP = [
        'منشور' => ProductStatus::Active,
        'مسودة' => ProductStatus::Draft,
        'مؤرشف' => ProductStatus::Archived,
    ];

    private const BOOL_VALUES = [
        'نعم' => true, 'yes' => true, '1' => true, 'true' => true,
        'لا' => false, 'no' => false, '0' => false, 'false' => false,
    ];

    // Sheet titles
    private const SHEET_PRODUCTS = 'المنتجات';

    private const SHEET_VARIANTS = 'الاختيارات';

    private const SHEET_SPECS = 'المواصفات';

    // -------------------------------------------------------------------------

    /** @var array<string, int> name_lowercase → id */
    private array $brandMap = [];

    /** @var array<string, int> "L1 > L2 > L3" → id */
    private array $categoryMap = [];

    /**
     * attr_code → [display_label_lowercase → attribute_value_id]
     *
     * @var array<string, array<string, int>>
     */
    private array $attrValueMap = [];

    /** @var array<string, string> attr_code → attribute Arabic name (for column label in errors) */
    private array $attrNames = [];

    /** @var list<array{sheet: string, row: int, column: string, message: string}> */
    private array $errors = [];

    /** @var list<array{sheet: string, row: int, column: string, message: string}> */
    private array $warnings = [];

    /** @var array<string, array<string, mixed>> sku → row data for valid product rows */
    private array $validProductRows = [];

    /** @var array<string, true> sku → true for products that have variant rows */
    private array $productHasVariants = [];

    /** @var array<string, true> sku → true for products with parse errors */
    private array $erroredProductSkus = [];

    /** @var array<string, list<array<string, mixed>>> productSku → valid variant rows (for the apply plan) */
    private array $validVariantRows = [];

    /** @var array<string, list<array<string, mixed>>> productSku → valid spec rows (for the apply plan) */
    private array $validSpecRows = [];

    private int $variantCount = 0;

    private int $specCount = 0;

    private int $imageCount = 0;

    // -------------------------------------------------------------------------

    /** @return array{errors: list<array<string,mixed>>, warnings: list<array<string,mixed>>, counts: array<string,int>, hasValidRows: bool, plan: list<array<string,mixed>>} */
    public function parse(string $filePath): array
    {
        $this->loadLookups();

        $spreadsheet = IOFactory::load($filePath);

        $this->parseProducts($spreadsheet->getSheetByName(self::SHEET_PRODUCTS));
        $this->parseVariants($spreadsheet->getSheetByName(self::SHEET_VARIANTS));
        $this->parseSpecs($spreadsheet->getSheetByName(self::SHEET_SPECS));
        $this->crossValidate();

        $validSkus = array_keys($this->validProductRows);
        $existingSkus = $validSkus
            ? Product::whereIn('sku', $validSkus)->pluck('sku')->flip()->all()
            : [];

        $createCount = 0;
        $updateCount = 0;
        foreach ($validSkus as $sku) {
            if (isset($existingSkus[$sku])) {
                $updateCount++;
            } else {
                $createCount++;
            }
        }

        return [
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'counts' => [
                'create' => $createCount,
                'update' => $updateCount,
                'variants' => $this->variantCount,
                'specs' => $this->specCount,
                'images' => $this->imageCount,
            ],
            'hasValidRows' => $createCount + $updateCount > 0,
            'plan' => $this->buildPlan(),
        ];
    }

    /**
     * Shape the valid rows into the structure the Apply job consumes. Only
     * products still valid after cross-validation are included, each with its
     * valid variant and spec rows.
     *
     * @return list<array<string, mixed>>
     */
    private function buildPlan(): array
    {
        $plan = [];

        foreach ($this->validProductRows as $sku => $row) {
            $cells = $row['raw'];
            $plan[] = [
                'excel_row' => $row['excel_row'],
                'sku' => $sku,
                'is_simple' => ! isset($this->productHasVariants[$sku]),
                'name' => ['ar' => $row['name_ar'], 'en' => $row['name_en']],
                'brand_id' => $row['brand_id'],
                'category_id' => $row['category_id'],
                'status' => $row['status'],
                'price_on_request' => $row['price_on_request'],
                'price' => $row['price'],
                'compare_at_price' => $row['compare_at_price'],
                'stock_qty' => $row['stock_qty'],
                // Optional translatable text — only locales whose cell was filled.
                'overview' => $this->partialTranslation($cells, 'overview'),
                'warranty' => $this->partialTranslation($cells, 'warranty'),
                'meta_title' => $this->partialTranslation($cells, 'meta_title'),
                'meta_description' => $this->partialTranslation($cells, 'meta_description'),
                // Optional booleans — null means "cell empty → keep current".
                'flags' => [
                    'is_new' => $this->parseBool($cells['is_new'] ?? null),
                    'is_best_seller' => $this->parseBool($cells['is_best_seller'] ?? null),
                    'is_smart_pick' => $this->parseBool($cells['is_smart_pick'] ?? null),
                    'is_dimmable' => $this->parseBool($cells['is_dimmable'] ?? null),
                    'ships_24h' => $this->parseBool($cells['ships_24h'] ?? null),
                ],
                'image_files' => $row['image_files'],
                'variants' => $this->validVariantRows[$sku] ?? [],
                'specs' => $this->validSpecRows[$sku] ?? [],
            ];
        }

        return $plan;
    }

    /**
     * @param  array<string, mixed>  $cells
     * @return array<string, string> only the locales whose `<base>_<locale>` cell was non-empty
     */
    private function partialTranslation(array $cells, string $base): array
    {
        $out = [];
        foreach (['ar', 'en'] as $locale) {
            $value = $this->str($cells[$base.'_'.$locale] ?? null);
            if ($value !== '') {
                $out[$locale] = $value;
            }
        }

        return $out;
    }

    // -------------------------------------------------------------------------
    // Lookup loaders
    // -------------------------------------------------------------------------

    private function loadLookups(): void
    {
        // Brands: name (plain string) → id
        Brand::query()->get(['id', 'name'])->each(function (Brand $b): void {
            $this->brandMap[mb_strtolower($b->name)] = $b->id;
        });

        // Categories: full Arabic path → id
        $categories = Category::where('level', 3)
            ->where('is_active', true)
            ->with(['parent.parent'])
            ->get();

        foreach ($categories as $cat) {
            $l3 = $cat->getTranslation('name', 'ar');
            $l2 = $cat->parent?->getTranslation('name', 'ar') ?? '';
            $l1 = $cat->parent?->parent?->getTranslation('name', 'ar') ?? '';
            $parts = array_filter([$l1, $l2, $l3], fn (string $p) => $p !== '');
            $path = implode(' > ', $parts);
            $this->categoryMap[$path] = $cat->id;
        }

        // Axis attribute values: attr_code → [display_label_lower → value_id]
        Attribute::where('is_variant_axis', true)
            ->with('values')
            ->get()
            ->each(function (Attribute $attr): void {
                $this->attrNames[$attr->code] = $attr->getTranslation('name', 'ar');
                $seen = [];
                $this->attrValueMap[$attr->code] = [];
                foreach ($attr->values as $val) {
                    $display = $val->getTranslation('label', 'ar') ?: $val->value;
                    $lower = mb_strtolower($display);
                    if (! isset($seen[$lower])) {
                        $this->attrValueMap[$attr->code][$lower] = $val->id;
                        $seen[$lower] = true;
                    }
                }
            });
    }

    // -------------------------------------------------------------------------
    // Sheet parsers
    // -------------------------------------------------------------------------

    private function parseProducts(?Worksheet $sheet): void
    {
        if ($sheet === null) {
            return;
        }

        $keyMap = $this->buildKeyMap($sheet);
        if (empty($keyMap)) {
            return;
        }

        $seenSkus = [];
        $highestRow = $sheet->getHighestRow();

        for ($row = 3; $row <= $highestRow; $row++) {
            if ($this->isRowEmpty($sheet, $keyMap, $row)) {
                continue;
            }

            $cells = $this->readRow($sheet, $keyMap, $row);
            $rowValid = true;

            // SKU
            $sku = $this->str($cells['sku'] ?? null);
            if ($sku === '') {
                $this->addError(self::SHEET_PRODUCTS, $row, 'sku', self::ERR_REQUIRED);
                $rowValid = false;
            } elseif (str_starts_with(strtoupper($sku), 'EXAMPLE-')) {
                $this->addError(self::SHEET_PRODUCTS, $row, 'sku', self::ERR_EXAMPLE_SKU);
                $rowValid = false;
            } elseif (isset($seenSkus[$sku])) {
                $this->addError(self::SHEET_PRODUCTS, $row, 'sku', self::ERR_DUPLICATE_SKU);
                $rowValid = false;
            }

            if ($rowValid) {
                $seenSkus[$sku] = true;
            }

            // Required text fields
            foreach (['name_ar', 'name_en'] as $key) {
                if ($this->str($cells[$key] ?? null) === '') {
                    $this->addError(self::SHEET_PRODUCTS, $row, $key, self::ERR_REQUIRED);
                    $rowValid = false;
                }
            }

            // Brand
            $brandName = $this->str($cells['brand'] ?? null);
            $brandId = null;
            if ($brandName === '') {
                $this->addError(self::SHEET_PRODUCTS, $row, 'brand', self::ERR_REQUIRED);
                $rowValid = false;
            } else {
                $brandId = $this->brandMap[mb_strtolower($brandName)] ?? null;
                if ($brandId === null) {
                    $this->addError(self::SHEET_PRODUCTS, $row, 'brand', self::ERR_BRAND_NOT_FOUND);
                    $rowValid = false;
                }
            }

            // Category
            $catPath = $this->str($cells['category'] ?? null);
            $catId = null;
            if ($catPath === '') {
                $this->addError(self::SHEET_PRODUCTS, $row, 'category', self::ERR_REQUIRED);
                $rowValid = false;
            } else {
                $catId = $this->categoryMap[$catPath] ?? null;
                if ($catId === null) {
                    $this->addError(self::SHEET_PRODUCTS, $row, 'category', self::ERR_CATEGORY_NOT_FOUND);
                    $rowValid = false;
                }
            }

            // Status (optional)
            $statusRaw = $this->str($cells['status'] ?? null);
            $statusEnum = null;
            if ($statusRaw !== '') {
                $statusEnum = self::STATUS_MAP[$statusRaw] ?? null;
                if ($statusEnum === null) {
                    $this->addError(self::SHEET_PRODUCTS, $row, 'status', self::ERR_INVALID_STATUS);
                    $rowValid = false;
                }
            }

            // Booleans
            $priceOnRequest = $this->parseBool($cells['price_on_request'] ?? null);

            // Price
            $priceRaw = $this->str($cells['price'] ?? null);
            $price = null;
            if ($priceRaw !== '') {
                $price = $this->parseDecimal($priceRaw);
                if ($price === false) {
                    $this->addError(self::SHEET_PRODUCTS, $row, 'price', self::ERR_PRICE_NOT_NUMERIC);
                    $rowValid = false;
                    $price = null;
                }
            }

            // compare_at_price
            $capRaw = $this->str($cells['compare_at_price'] ?? null);
            $cap = null;
            if ($capRaw !== '') {
                $cap = $this->parseDecimal($capRaw);
                if ($cap === false) {
                    $this->addError(self::SHEET_PRODUCTS, $row, 'compare_at_price', self::ERR_COMPARE_NOT_NUMERIC);
                    $rowValid = false;
                    $cap = null;
                } elseif ($price !== null && $cap <= $price) {
                    $this->addError(self::SHEET_PRODUCTS, $row, 'compare_at_price', self::ERR_COMPARE_NOT_GREATER);
                    $rowValid = false;
                    $cap = null;
                }
            }

            // stock_qty
            $stockRaw = $this->str($cells['stock_qty'] ?? null);
            $stock = null;
            if ($stockRaw !== '') {
                $stock = $this->parseInteger($stockRaw);
                if ($stock === false) {
                    $this->addError(self::SHEET_PRODUCTS, $row, 'stock_qty', self::ERR_STOCK_INVALID);
                    $rowValid = false;
                    $stock = null;
                }
            }

            // Images count
            $imagesRaw = $this->str($cells['images'] ?? null);
            $imageFiles = $imagesRaw !== ''
                ? array_filter(array_map('trim', explode('|', $imagesRaw)))
                : [];

            if ($rowValid) {
                $this->validProductRows[$sku] = [
                    'excel_row' => $row,
                    'sku' => $sku,
                    'name_ar' => $this->str($cells['name_ar'] ?? null),
                    'name_en' => $this->str($cells['name_en'] ?? null),
                    'brand_id' => $brandId,
                    'category_id' => $catId,
                    'status' => $statusEnum,
                    'price' => $price,
                    'compare_at_price' => $cap,
                    'stock_qty' => $stock,
                    'price_on_request' => $priceOnRequest,
                    'has_price' => $priceRaw !== '' || $capRaw !== '' || $stockRaw !== '',
                    'image_files' => $imageFiles,
                    // store remaining translatable/boolean fields as-is for BI.3
                    'raw' => $cells,
                ];
                $this->imageCount += count($imageFiles);
            } elseif ($sku !== '') {
                $this->erroredProductSkus[$sku] = true;
            }
        }
    }

    private function parseVariants(?Worksheet $sheet): void
    {
        if ($sheet === null) {
            return;
        }

        $keyMap = $this->buildKeyMap($sheet);
        if (empty($keyMap)) {
            return;
        }

        $highestRow = $sheet->getHighestRow();

        for ($row = 3; $row <= $highestRow; $row++) {
            if ($this->isRowEmpty($sheet, $keyMap, $row)) {
                continue;
            }

            $cells = $this->readRow($sheet, $keyMap, $row);
            $rowValid = true;

            // product_sku
            $productSku = $this->str($cells['product_sku'] ?? null);
            if ($productSku === '') {
                $this->addError(self::SHEET_VARIANTS, $row, 'product_sku', self::ERR_REQUIRED);
                $rowValid = false;
            } elseif (isset($this->erroredProductSkus[$productSku])) {
                $this->addError(self::SHEET_VARIANTS, $row, 'product_sku', self::ERR_PARENT_SKU_ERRORED);
                $rowValid = false;
            } elseif (! isset($this->validProductRows[$productSku])) {
                $this->addError(self::SHEET_VARIANTS, $row, 'product_sku', self::ERR_PARENT_SKU_NOT_FOUND);
                $rowValid = false;
            }

            // variant_sku
            $variantSku = $this->str($cells['variant_sku'] ?? null);
            if ($variantSku === '') {
                $this->addError(self::SHEET_VARIANTS, $row, 'variant_sku', self::ERR_REQUIRED);
                $rowValid = false;
            } elseif (str_starts_with(strtoupper($variantSku), 'EXAMPLE-')) {
                $this->addError(self::SHEET_VARIANTS, $row, 'variant_sku', self::ERR_EXAMPLE_SKU);
                $rowValid = false;
            } elseif ($productSku !== '' && $variantSku === $productSku) {
                $this->addError(self::SHEET_VARIANTS, $row, 'variant_sku', self::ERR_VARIANT_SKU_SAME_AS_PRODUCT);
                $rowValid = false;
            }

            // Attribute values
            $valueIds = [];
            foreach ($keyMap as $key => $col) {
                if (! str_starts_with($key, 'attr:')) {
                    continue;
                }
                $attrCode = substr($key, 5);
                $cellVal = $this->str($cells[$key] ?? null);
                if ($cellVal === '') {
                    continue; // optional
                }
                $valueId = $this->attrValueMap[$attrCode][mb_strtolower($cellVal)] ?? null;
                if ($valueId === null) {
                    $colLabel = $this->attrNames[$attrCode] ?? $key;
                    $this->addError(self::SHEET_VARIANTS, $row, $colLabel, self::ERR_ATTR_VALUE_NOT_FOUND);
                    $rowValid = false;
                } else {
                    $valueIds[] = $valueId;
                }
            }

            // price (required unless parent has price_on_request)
            $parentPor = isset($this->validProductRows[$productSku])
                ? ($this->validProductRows[$productSku]['price_on_request'] === true)
                : false;
            $priceRaw = $this->str($cells['price'] ?? null);
            $price = null;
            if ($priceRaw !== '') {
                $price = $this->parseDecimal($priceRaw);
                if ($price === false) {
                    $this->addError(self::SHEET_VARIANTS, $row, 'price', self::ERR_PRICE_NOT_NUMERIC);
                    $rowValid = false;
                    $price = null;
                }
            } elseif (! $parentPor) {
                $this->addError(self::SHEET_VARIANTS, $row, 'price', self::ERR_PRICE_REQUIRED);
                $rowValid = false;
            }

            // compare_at_price
            $capRaw = $this->str($cells['compare_at_price'] ?? null);
            $cap = null;
            if ($capRaw !== '') {
                $cap = $this->parseDecimal($capRaw);
                if ($cap === false) {
                    $this->addError(self::SHEET_VARIANTS, $row, 'compare_at_price', self::ERR_COMPARE_NOT_NUMERIC);
                    $rowValid = false;
                    $cap = null;
                } elseif ($price !== null && $cap <= $price) {
                    $this->addError(self::SHEET_VARIANTS, $row, 'compare_at_price', self::ERR_COMPARE_NOT_GREATER);
                    $rowValid = false;
                    $cap = null;
                }
            }

            // stock_qty (required)
            $stockRaw = $this->str($cells['stock_qty'] ?? null);
            $stock = null;
            if ($stockRaw === '') {
                $this->addError(self::SHEET_VARIANTS, $row, 'stock_qty', self::ERR_REQUIRED);
                $rowValid = false;
            } else {
                $stock = $this->parseInteger($stockRaw);
                if ($stock === false) {
                    $this->addError(self::SHEET_VARIANTS, $row, 'stock_qty', self::ERR_STOCK_INVALID);
                    $rowValid = false;
                    $stock = null;
                }
            }

            if ($rowValid) {
                $this->productHasVariants[$productSku] = true;
                $this->variantCount++;
                $this->validVariantRows[$productSku][] = [
                    'variant_sku' => $variantSku,
                    'value_ids' => $valueIds,
                    'price' => $price,
                    'compare_at_price' => $cap,
                    'stock_qty' => $stock,
                    'is_default' => $this->parseBool($cells['is_default'] ?? null) === true,
                ];
            }
        }
    }

    private function parseSpecs(?Worksheet $sheet): void
    {
        if ($sheet === null) {
            return;
        }

        $keyMap = $this->buildKeyMap($sheet);
        if (empty($keyMap)) {
            return;
        }

        $highestRow = $sheet->getHighestRow();

        for ($row = 3; $row <= $highestRow; $row++) {
            if ($this->isRowEmpty($sheet, $keyMap, $row)) {
                continue;
            }

            $cells = $this->readRow($sheet, $keyMap, $row);
            $rowValid = true;

            $productSku = $this->str($cells['product_sku'] ?? null);
            if ($productSku === '') {
                $this->addError(self::SHEET_SPECS, $row, 'product_sku', self::ERR_REQUIRED);
                $rowValid = false;
            } elseif (isset($this->erroredProductSkus[$productSku])) {
                $this->addError(self::SHEET_SPECS, $row, 'product_sku', self::ERR_PARENT_SKU_ERRORED);
                $rowValid = false;
            } elseif (! isset($this->validProductRows[$productSku])) {
                $this->addError(self::SHEET_SPECS, $row, 'product_sku', self::ERR_PARENT_SKU_NOT_FOUND);
                $rowValid = false;
            }

            foreach (['name_ar', 'name_en', 'value_ar', 'value_en'] as $key) {
                if ($this->str($cells[$key] ?? null) === '') {
                    $this->addError(self::SHEET_SPECS, $row, $key, self::ERR_REQUIRED);
                    $rowValid = false;
                }
            }

            if ($rowValid) {
                $this->specCount++;
                $this->validSpecRows[$productSku][] = [
                    'name' => ['ar' => $this->str($cells['name_ar'] ?? null), 'en' => $this->str($cells['name_en'] ?? null)],
                    'value' => ['ar' => $this->str($cells['value_ar'] ?? null), 'en' => $this->str($cells['value_en'] ?? null)],
                ];
            }
        }
    }

    private function crossValidate(): void
    {
        // Variant products must not have price/stock in the Products sheet.
        foreach ($this->validProductRows as $sku => $row) {
            if (isset($this->productHasVariants[$sku]) && $row['has_price']) {
                $this->addError(self::SHEET_PRODUCTS, $row['excel_row'], 'price', self::ERR_VARIANT_PRODUCT_HAS_PRICE);
                unset($this->validProductRows[$sku]);
                $this->erroredProductSkus[$sku] = true;
                // Subtract image count added for this product
                $this->imageCount -= count($row['image_files']);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @return array<string, string> key → column letter */
    private function buildKeyMap(Worksheet $sheet): array
    {
        $map = [];
        $highestCol = $sheet->getHighestColumn();
        $highestColIndex = Coordinate::columnIndexFromString($highestCol);

        for ($i = 1; $i <= $highestColIndex; $i++) {
            $col = Coordinate::stringFromColumnIndex($i);
            $val = trim((string) ($sheet->getCell($col.'2')->getValue() ?? ''));
            if ($val !== '') {
                $map[$val] = $col;
            }
        }

        return $map;
    }

    /** @param array<string, string> $keyMap
     *  @return array<string, mixed> */
    private function readRow(Worksheet $sheet, array $keyMap, int $row): array
    {
        $data = [];
        foreach ($keyMap as $key => $col) {
            $data[$key] = $sheet->getCell($col.$row)->getValue();
        }

        return $data;
    }

    /** @param array<string, string> $keyMap */
    private function isRowEmpty(Worksheet $sheet, array $keyMap, int $row): bool
    {
        foreach ($keyMap as $col) {
            $val = $sheet->getCell($col.$row)->getValue();
            if ($val !== null && trim((string) $val) !== '') {
                return false;
            }
        }

        return true;
    }

    private function str(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    private function parseBool(mixed $value): ?bool
    {
        $v = mb_strtolower(trim((string) ($value ?? '')));
        if ($v === '') {
            return null;
        }

        return self::BOOL_VALUES[$v] ?? null;
    }

    private function parseDecimal(string $value): float|false
    {
        $v = str_replace([',', ' '], '', $value);
        if (! is_numeric($v)) {
            return false;
        }
        $n = (float) $v;

        return $n < 0 ? false : $n;
    }

    private function parseInteger(string $value): int|false
    {
        $v = str_replace([',', ' '], '', $value);
        if (! is_numeric($v)) {
            return false;
        }
        $f = (float) $v;
        if ($f < 0) {
            return false;
        }
        // Allow .0 (e.g. "10.0" from Excel)
        if (fmod($f, 1.0) !== 0.0) {
            return false;
        }

        return (int) $f;
    }

    private function addError(string $sheet, int $row, string $column, string $message): void
    {
        $this->errors[] = [
            'sheet' => $sheet,
            'row' => $row,
            'column' => $column,
            'message' => $message,
        ];
    }
}
