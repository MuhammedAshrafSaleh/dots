<?php

use App\Livewire\Admin\Products\Import;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSpec;
use App\Models\ProductVariant;
use App\Services\ProductImportParser;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

beforeEach(fn () => loginAdmin());

/** Creates a 3-level active category and returns the level-3 instance. */
function bi2Category(string $l1Ar, string $l2Ar, string $l3Ar): Category
{
    $makeSlug = fn (string $s) => Str::slug($s) ?: 'cat-'.uniqid();

    $l1 = Category::where('level', 1)->where('name->ar', $l1Ar)->first()
        ?? Category::create(['level' => 1, 'is_active' => true, 'name' => ['ar' => $l1Ar, 'en' => $l1Ar], 'slug' => ['ar' => $makeSlug($l1Ar), 'en' => $makeSlug($l1Ar)]]);

    $l2 = Category::where('level', 2)->where('name->ar', $l2Ar)->where('parent_id', $l1->id)->first()
        ?? Category::create(['level' => 2, 'parent_id' => $l1->id, 'is_active' => true, 'name' => ['ar' => $l2Ar, 'en' => $l2Ar], 'slug' => ['ar' => $makeSlug($l2Ar), 'en' => $makeSlug($l2Ar)]]);

    return Category::where('level', 3)->where('name->ar', $l3Ar)->where('parent_id', $l2->id)->first()
        ?? Category::create(['level' => 3, 'parent_id' => $l2->id, 'is_active' => true, 'name' => ['ar' => $l3Ar, 'en' => $l3Ar], 'slug' => ['ar' => $makeSlug($l3Ar), 'en' => $makeSlug($l3Ar)]]);
}

/** Creates an is_variant_axis Attribute with given values. */
function bi2Attr(string $code, string $nameAr, array $values): Attribute
{
    $attr = Attribute::firstOrCreate(
        ['code' => $code],
        ['name' => ['ar' => $nameAr, 'en' => $nameAr], 'is_variant_axis' => true, 'is_filterable' => false],
    );
    foreach ($values as $i => $v) {
        AttributeValue::firstOrCreate(
            ['attribute_id' => $attr->id, 'value' => $v],
            ['label' => ['ar' => $v, 'en' => $v], 'sort_order' => $i],
        );
    }

    return $attr->load('values');
}

/**
 * Build a minimal import xlsx and return the temp file path.
 * Caller is responsible for unlinking the file after use.
 *
 * @param  array<int, array<string, mixed>>  $productRows  rows for المنتجات (keyed by column key)
 * @param  array<int, array<string, mixed>>  $variantRows  rows for الاختيارات
 * @param  array<int, array<string, mixed>>  $specRows  rows for المواصفات
 * @param  array<int, string>|null  $productColOrder  custom column order for المنتجات (for shuffle test)
 */
function buildImportXlsx(
    array $productRows = [],
    array $variantRows = [],
    array $specRows = [],
    ?array $productColOrder = null,
): string {
    $spreadsheet = new Spreadsheet;
    $spreadsheet->removeSheetByIndex(0);

    // --- المنتجات ---
    $productSheet = new Worksheet($spreadsheet, 'المنتجات');
    $spreadsheet->addSheet($productSheet);

    $defaultProductKeys = [
        'sku', 'name_ar', 'name_en', 'brand', 'category', 'status',
        'price', 'compare_at_price', 'stock_qty', 'price_on_request', 'images',
    ];
    $productKeys = $productColOrder ?? $defaultProductKeys;

    foreach ($productKeys as $i => $key) {
        $col = Coordinate::stringFromColumnIndex($i + 1);
        $productSheet->setCellValue($col.'1', $key);
        $productSheet->setCellValue($col.'2', $key);
    }

    foreach ($productRows as $j => $row) {
        $excelRow = $j + 3;
        foreach ($productKeys as $i => $key) {
            if (array_key_exists($key, $row)) {
                $col = Coordinate::stringFromColumnIndex($i + 1);
                $productSheet->setCellValue($col.$excelRow, $row[$key]);
            }
        }
    }

    // --- الاختيارات ---
    $varSheet = new Worksheet($spreadsheet, 'الاختيارات');
    $spreadsheet->addSheet($varSheet);

    $varKeys = ['product_sku', 'variant_sku', 'price', 'compare_at_price', 'stock_qty', 'is_default'];
    foreach ($varKeys as $i => $key) {
        $col = Coordinate::stringFromColumnIndex($i + 1);
        $varSheet->setCellValue($col.'2', $key);
    }
    foreach ($variantRows as $j => $row) {
        $excelRow = $j + 3;
        foreach ($varKeys as $i => $key) {
            if (array_key_exists($key, $row)) {
                $col = Coordinate::stringFromColumnIndex($i + 1);
                $varSheet->setCellValue($col.$excelRow, $row[$key]);
            }
        }
    }

    // --- المواصفات ---
    $specSheet = new Worksheet($spreadsheet, 'المواصفات');
    $spreadsheet->addSheet($specSheet);

    $specKeys = ['product_sku', 'name_ar', 'name_en', 'value_ar', 'value_en'];
    foreach ($specKeys as $i => $key) {
        $col = Coordinate::stringFromColumnIndex($i + 1);
        $specSheet->setCellValue($col.'2', $key);
    }
    foreach ($specRows as $j => $row) {
        $excelRow = $j + 3;
        foreach ($specKeys as $i => $key) {
            if (array_key_exists($key, $row)) {
                $col = Coordinate::stringFromColumnIndex($i + 1);
                $specSheet->setCellValue($col.$excelRow, $row[$key]);
            }
        }
    }

    $path = tempnam(sys_get_temp_dir(), 'dots_import_bi2_').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return $path;
}

// ---------------------------------------------------------------------------
// EXAMPLE- SKUs rejected
// ---------------------------------------------------------------------------

it('rejects product rows with EXAMPLE- SKU', function () {
    $brand = Brand::firstOrCreate(['slug' => 'test-brand-bi2'], ['name' => 'TestBrand']);
    $cat = bi2Category('إضاءة', 'داخلية', 'داون لايت');

    $path = buildImportXlsx([
        ['sku' => 'EXAMPLE-001', 'name_ar' => 'منتج', 'name_en' => 'Product', 'brand' => 'TestBrand', 'category' => 'إضاءة > داخلية > داون لايت', 'price' => '100', 'stock_qty' => '5'],
    ]);

    $result = (new ProductImportParser)->parse($path);
    unlink($path);

    $skuErrors = collect($result['errors'])->where('column', 'sku');
    expect($skuErrors)->not->toBeEmpty();
    expect($result['hasValidRows'])->toBeFalse();
});

it('rejects variant rows with EXAMPLE- SKU', function () {
    $brand = Brand::firstOrCreate(['slug' => 'test-brand-bi2-v'], ['name' => 'TestBrandV']);
    $cat = bi2Category('إضاءة', 'داخلية', 'داون لايت');

    $path = buildImportXlsx(
        productRows: [
            ['sku' => 'PROD-VALID', 'name_ar' => 'منتج', 'name_en' => 'Product', 'brand' => 'TestBrandV', 'category' => 'إضاءة > داخلية > داون لايت'],
        ],
        variantRows: [
            ['product_sku' => 'PROD-VALID', 'variant_sku' => 'EXAMPLE-VAR-001', 'price' => '100', 'stock_qty' => '5'],
        ],
    );

    $result = (new ProductImportParser)->parse($path);
    unlink($path);

    $varErrors = collect($result['errors'])->where('sheet', 'الاختيارات');
    expect($varErrors)->not->toBeEmpty();
});

// ---------------------------------------------------------------------------
// Reference data validation
// ---------------------------------------------------------------------------

it('rejects unknown brand', function () {
    $cat = bi2Category('إضاءة', 'داخلية', 'داون لايت');

    $path = buildImportXlsx([
        ['sku' => 'SKU-BR-01', 'name_ar' => 'منتج', 'name_en' => 'Product', 'brand' => 'BrandThatDoesNotExist', 'category' => 'إضاءة > داخلية > داون لايت', 'price' => '100', 'stock_qty' => '0'],
    ]);

    $result = (new ProductImportParser)->parse($path);
    unlink($path);

    $errors = collect($result['errors'])->where('column', 'brand');
    expect($errors)->not->toBeEmpty();
});

it('rejects unknown category path', function () {
    $brand = Brand::firstOrCreate(['slug' => 'brand-cat-test'], ['name' => 'BrandCatTest']);

    $path = buildImportXlsx([
        ['sku' => 'SKU-CAT-01', 'name_ar' => 'منتج', 'name_en' => 'Product', 'brand' => 'BrandCatTest', 'category' => 'قسم > غير > موجود', 'price' => '100', 'stock_qty' => '0'],
    ]);

    $result = (new ProductImportParser)->parse($path);
    unlink($path);

    $errors = collect($result['errors'])->where('column', 'category');
    expect($errors)->not->toBeEmpty();
});

// ---------------------------------------------------------------------------
// Simple-vs-variant conflict
// ---------------------------------------------------------------------------

it('rejects a product row that has price when it also has variant rows', function () {
    $brand = Brand::firstOrCreate(['slug' => 'brand-sv'], ['name' => 'BrandSV']);
    $cat = bi2Category('إضاءة', 'داخلية', 'داون لايت');

    // Product row has price AND there are variant rows for it
    $path = buildImportXlsx(
        productRows: [
            ['sku' => 'P-SV-01', 'name_ar' => 'منتج', 'name_en' => 'Product', 'brand' => 'BrandSV', 'category' => 'إضاءة > داخلية > داون لايت', 'price' => '250', 'stock_qty' => '10'],
        ],
        variantRows: [
            ['product_sku' => 'P-SV-01', 'variant_sku' => 'P-SV-01-V1', 'price' => '250', 'stock_qty' => '10'],
        ],
    );

    $result = (new ProductImportParser)->parse($path);
    unlink($path);

    $errors = collect($result['errors'])->where('column', 'price');
    expect($errors)->not->toBeEmpty();
    expect($result['hasValidRows'])->toBeFalse();
});

// ---------------------------------------------------------------------------
// Numeric validation
// ---------------------------------------------------------------------------

it('rejects non-numeric price', function () {
    $brand = Brand::firstOrCreate(['slug' => 'brand-num'], ['name' => 'BrandNum']);
    $cat = bi2Category('إضاءة', 'داخلية', 'داون لايت');

    $path = buildImportXlsx([
        ['sku' => 'SKU-NUM-01', 'name_ar' => 'منتج', 'name_en' => 'Product', 'brand' => 'BrandNum', 'category' => 'إضاءة > داخلية > داون لايت', 'price' => 'مائة', 'stock_qty' => '5'],
    ]);

    $result = (new ProductImportParser)->parse($path);
    unlink($path);

    $errors = collect($result['errors'])->where('column', 'price');
    expect($errors)->not->toBeEmpty();
});

it('rejects negative stock', function () {
    $brand = Brand::firstOrCreate(['slug' => 'brand-neg-stock'], ['name' => 'BrandNegStock']);
    $cat = bi2Category('إضاءة', 'داخلية', 'داون لايت');

    $path = buildImportXlsx([
        ['sku' => 'SKU-NEG-01', 'name_ar' => 'منتج', 'name_en' => 'Product', 'brand' => 'BrandNegStock', 'category' => 'إضاءة > داخلية > داون لايت', 'price' => '100', 'stock_qty' => '-3'],
    ]);

    $result = (new ProductImportParser)->parse($path);
    unlink($path);

    $errors = collect($result['errors'])->where('column', 'stock_qty');
    expect($errors)->not->toBeEmpty();
});

it('rejects compare_at_price equal to or less than price', function () {
    $brand = Brand::firstOrCreate(['slug' => 'brand-cap'], ['name' => 'BrandCap']);
    $cat = bi2Category('إضاءة', 'داخلية', 'داون لايت');

    $path = buildImportXlsx([
        // compare_at_price == price
        ['sku' => 'SKU-CAP-01', 'name_ar' => 'منتج', 'name_en' => 'Product', 'brand' => 'BrandCap', 'category' => 'إضاءة > داخلية > داون لايت', 'price' => '200', 'compare_at_price' => '200', 'stock_qty' => '0'],
    ]);

    $result = (new ProductImportParser)->parse($path);
    unlink($path);

    $errors = collect($result['errors'])->where('column', 'compare_at_price');
    expect($errors)->not->toBeEmpty();
});

// ---------------------------------------------------------------------------
// Positional independence (shuffled columns)
// ---------------------------------------------------------------------------

it('maps columns by key regardless of column position', function () {
    $brand = Brand::firstOrCreate(['slug' => 'brand-shuffle'], ['name' => 'BrandShuffle']);
    $cat = bi2Category('إضاءة', 'داخلية', 'داون لايت');

    // Reversed column order: category, brand, name_en, name_ar, sku, price, stock_qty
    $shuffledOrder = ['category', 'brand', 'name_en', 'name_ar', 'sku', 'price', 'stock_qty'];

    $path = buildImportXlsx(
        productRows: [[
            'sku' => 'SKU-SHUFFLE-001',
            'name_ar' => 'منتج مخلوط',
            'name_en' => 'Shuffled Product',
            'brand' => 'BrandShuffle',
            'category' => 'إضاءة > داخلية > داون لايت',
            'price' => '150',
            'stock_qty' => '3',
        ]],
        productColOrder: $shuffledOrder,
    );

    $result = (new ProductImportParser)->parse($path);
    unlink($path);

    expect($result['errors'])->toBeEmpty()
        ->and($result['hasValidRows'])->toBeTrue();
});

// ---------------------------------------------------------------------------
// No DB writes during parse
// ---------------------------------------------------------------------------

it('writes zero rows to products/variants/specs during a parse', function () {
    $brand = Brand::firstOrCreate(['slug' => 'brand-nodbc'], ['name' => 'BrandNoDb']);
    $cat = bi2Category('إضاءة', 'داخلية', 'داون لايت');

    $path = buildImportXlsx(
        productRows: [
            ['sku' => 'SKU-DRYRUN-001', 'name_ar' => 'منتج', 'name_en' => 'Product', 'brand' => 'BrandNoDb', 'category' => 'إضاءة > داخلية > داون لايت', 'price' => '100', 'stock_qty' => '5'],
        ],
        specRows: [
            ['product_sku' => 'SKU-DRYRUN-001', 'name_ar' => 'الوزن', 'name_en' => 'Weight', 'value_ar' => '1 كجم', 'value_en' => '1 kg'],
        ],
    );

    $productCountBefore = Product::count();
    $variantCountBefore = ProductVariant::count();
    $specCountBefore = ProductSpec::count();

    (new ProductImportParser)->parse($path);
    unlink($path);

    expect(Product::count())->toBe($productCountBefore)
        ->and(ProductVariant::count())->toBe($variantCountBefore)
        ->and(ProductSpec::count())->toBe($specCountBefore);
});

// ---------------------------------------------------------------------------
// Livewire: authorization
// ---------------------------------------------------------------------------

it('preview() returns 403 without products.import permission', function () {
    loginAdmin(['dashboard.view']);
    Livewire::test(Import::class)->call('preview')->assertForbidden();
});

it('downloadReport() returns 403 without products.import permission', function () {
    loginAdmin(['dashboard.view']);
    Livewire::test(Import::class)->call('downloadReport')->assertForbidden();
});

it('upload card is hidden without products.import permission', function () {
    loginAdmin(['products.export']); // export only
    Livewire::test(Import::class)
        ->assertDontSee(__('admin.import_products.label_xlsx'));
});

// ---------------------------------------------------------------------------
// Preview counts and error table via Livewire
// ---------------------------------------------------------------------------

it('preview shows error count when xlsx has bad rows', function () {
    $cat = bi2Category('إضاءة', 'داخلية', 'داون لايت');
    $brand = Brand::firstOrCreate(['slug' => 'brand-prev-err'], ['name' => 'BrandPrevErr']);

    // One good row, one with an EXAMPLE- SKU
    $path = buildImportXlsx([
        ['sku' => 'GOOD-001', 'name_ar' => 'منتج', 'name_en' => 'Product', 'brand' => 'BrandPrevErr', 'category' => 'إضاءة > داخلية > داون لايت', 'price' => '100', 'stock_qty' => '0'],
        ['sku' => 'EXAMPLE-BAD', 'name_ar' => 'مثال', 'name_en' => 'Example', 'brand' => 'BrandPrevErr', 'category' => 'إضاءة > داخلية > داون لايت', 'price' => '200', 'stock_qty' => '0'],
    ]);

    // Test the parser directly (Livewire file upload is covered by manual smoke test)
    $result = (new ProductImportParser)->parse($path);
    unlink($path);

    expect(count($result['errors']))->toBeGreaterThan(0)
        ->and($result['hasValidRows'])->toBeTrue()
        ->and($result['counts']['create'])->toBe(1);
});
