<?php

use App\Actions\UpdateProductCacheColumns;
use App\Exceptions\MediaImportException;
use App\Livewire\Admin\Products\Import;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ImportLog;
use App\Models\Product;
use App\Models\ProductSpec;
use App\Models\ProductVariant;
use App\Services\ProductExportBuilder;
use App\Services\ProductImportApplier;
use App\Services\ProductImportParser;
use App\Services\ProductMediaExtractor;
use App\Support\CacheTags;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(fn () => loginAdmin());

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function bi3Category(): Category
{
    $slug = fn (string $s) => Str::slug($s) ?: 'c-'.uniqid();
    $l1 = Category::where('level', 1)->where('name->ar', 'إضاءة')->first()
        ?? Category::create(['level' => 1, 'is_active' => true, 'name' => ['ar' => 'إضاءة', 'en' => 'Lighting'], 'slug' => ['ar' => $slug('اضاءة'), 'en' => $slug('lighting')]]);
    $l2 = Category::where('level', 2)->where('name->ar', 'داخلية')->where('parent_id', $l1->id)->first()
        ?? Category::create(['level' => 2, 'parent_id' => $l1->id, 'is_active' => true, 'name' => ['ar' => 'داخلية', 'en' => 'Indoor'], 'slug' => ['ar' => $slug('داخلية'), 'en' => $slug('indoor')]]);

    return Category::where('level', 3)->where('name->ar', 'داون لايت')->where('parent_id', $l2->id)->first()
        ?? Category::create(['level' => 3, 'parent_id' => $l2->id, 'is_active' => true, 'name' => ['ar' => 'داون لايت', 'en' => 'Downlight'], 'slug' => ['ar' => $slug('داون-لايت'), 'en' => $slug('downlight')]]);
}

function bi3Brand(): Brand
{
    return Brand::firstOrCreate(['slug' => 'bi3-brand'], ['name' => 'BI3Brand']);
}

function bi3Attr(): Attribute
{
    $attr = Attribute::firstOrCreate(['code' => 'cct'], ['name' => ['ar' => 'درجة اللون', 'en' => 'CCT'], 'is_variant_axis' => true, 'is_filterable' => false]);
    foreach (['2700', '3000'] as $i => $v) {
        AttributeValue::firstOrCreate(['attribute_id' => $attr->id, 'value' => $v], ['label' => ['ar' => $v, 'en' => $v], 'sort_order' => $i]);
    }

    return $attr->load('values');
}

/**
 * Flexible import xlsx builder (المنتجات + الاختيارات + المواصفات).
 *
 * @param  array<int, array<string, mixed>>  $productRows
 * @param  array<int, array<string, mixed>>  $variantRows
 * @param  array<int, array<string, mixed>>  $specRows
 * @param  array<int, string>  $variantKeys
 */
function bi3Xlsx(array $productRows, array $variantRows = [], array $specRows = [], array $variantKeys = ['product_sku', 'variant_sku', 'attr:cct', 'price', 'compare_at_price', 'stock_qty', 'is_default']): string
{
    $ss = new Spreadsheet;
    $ss->removeSheetByIndex(0);

    $productKeys = ['sku', 'name_ar', 'name_en', 'brand', 'category', 'status', 'price', 'compare_at_price', 'stock_qty', 'price_on_request', 'overview_ar', 'overview_en', 'is_new', 'images'];

    $write = function (Worksheet $sheet, array $keys, array $rows): void {
        foreach ($keys as $i => $key) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).'2', $key);
        }
        foreach ($rows as $j => $row) {
            foreach ($keys as $i => $key) {
                if (array_key_exists($key, $row)) {
                    $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($i + 1).($j + 3), (string) $row[$key], DataType::TYPE_STRING2);
                }
            }
        }
    };

    $ps = new Worksheet($ss, 'المنتجات');
    $ss->addSheet($ps);
    $write($ps, $productKeys, $productRows);

    $vs = new Worksheet($ss, 'الاختيارات');
    $ss->addSheet($vs);
    $write($vs, $variantKeys, $variantRows);

    $sps = new Worksheet($ss, 'المواصفات');
    $ss->addSheet($sps);
    $write($sps, ['product_sku', 'name_ar', 'name_en', 'value_ar', 'value_en'], $specRows);

    $path = tempnam(sys_get_temp_dir(), 'dots_bi3_').'.xlsx';
    (new Xlsx($ss))->save($path);

    return $path;
}

/** Apply an xlsx (+ optional media) through parser → applier, return [counts, warnings]. */
function bi3Apply(string $xlsxPath, ?array $mediaFiles = null): array
{
    $parsed = (new ProductImportParser)->parse($xlsxPath);
    $warnings = $parsed['warnings'];
    $counts = app(ProductImportApplier::class)->apply($parsed['plan'], $mediaFiles, $warnings);

    return [$counts, $warnings, $parsed];
}

/** Build a ZIP from [name => contents] and return its path. */
function bi3Zip(array $entries): string
{
    $path = tempnam(sys_get_temp_dir(), 'dots_zip_').'.zip';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($entries as $name => $contents) {
        $zip->addFromString($name, $contents);
    }
    $zip->close();

    return $path;
}

function bi3PngBytes(): string
{
    $img = imagecreatetruecolor(8, 8);
    imagefill($img, 0, 0, imagecolorallocate($img, 10, 20, 30));
    ob_start();
    imagepng($img);
    $bytes = (string) ob_get_clean();
    imagedestroy($img);

    return $bytes;
}

// ---------------------------------------------------------------------------
// Data: empty cell keeps current value
// ---------------------------------------------------------------------------

it('keeps the current value when a cell is empty on update', function () {
    $brand = bi3Brand();
    $cat = bi3Category();
    $product = Product::create(['sku' => 'KEEP-1', 'name' => ['ar' => 'قديم', 'en' => 'Old'], 'slug' => ['ar' => 'keep-ar', 'en' => 'keep-en'], 'brand_id' => $brand->id, 'category_id' => $cat->id, 'status' => 'active', 'published_at' => now()]);
    $product->variants()->create(['sku' => 'KEEP-1', 'price' => 100, 'stock_qty' => 5, 'is_default' => true, 'is_active' => true]);

    // Re-import same SKU with a new name but NO price cell.
    $path = bi3Xlsx([
        ['sku' => 'KEEP-1', 'name_ar' => 'جديد', 'name_en' => 'New', 'brand' => 'BI3Brand', 'category' => 'إضاءة > داخلية > داون لايت'],
    ]);
    [$counts] = bi3Apply($path);
    unlink($path);

    $variant = $product->variants()->where('sku', 'KEEP-1')->first();
    expect($counts['updated'])->toBe(1)
        ->and((float) $variant->price)->toBe(100.0)
        ->and($product->fresh()->getTranslation('name', 'en'))->toBe('New');
});

// ---------------------------------------------------------------------------
// Variant sync: missing variant deactivated, not deleted
// ---------------------------------------------------------------------------

it('deactivates a variant missing from the file instead of deleting it', function () {
    $brand = bi3Brand();
    $cat = bi3Category();
    $attr = bi3Attr();
    $v2700 = $attr->values->firstWhere('value', '2700');
    $v3000 = $attr->values->firstWhere('value', '3000');

    $product = Product::create(['sku' => 'VP-1', 'name' => ['ar' => 'منتج', 'en' => 'Product'], 'slug' => ['ar' => 'vp-ar', 'en' => 'vp-en'], 'brand_id' => $brand->id, 'category_id' => $cat->id, 'status' => 'active', 'published_at' => now()]);
    $a = $product->variants()->create(['sku' => 'VP-1-A', 'price' => 100, 'stock_qty' => 5, 'is_default' => true, 'is_active' => true]);
    $a->attributeValues()->sync([$v2700->id]);
    $b = $product->variants()->create(['sku' => 'VP-1-B', 'price' => 120, 'stock_qty' => 5, 'is_active' => true]);
    $b->attributeValues()->sync([$v3000->id]);

    // File lists only VP-1-A.
    $path = bi3Xlsx(
        [['sku' => 'VP-1', 'name_ar' => 'منتج', 'name_en' => 'Product', 'brand' => 'BI3Brand', 'category' => 'إضاءة > داخلية > داون لايت']],
        [['product_sku' => 'VP-1', 'variant_sku' => 'VP-1-A', 'attr:cct' => '2700', 'price' => '100', 'stock_qty' => '5', 'is_default' => 'نعم']],
    );
    bi3Apply($path);
    unlink($path);

    $b->refresh();
    expect($b->exists)->toBeTrue()
        ->and($b->is_active)->toBeFalse()
        ->and($product->variants()->where('sku', 'VP-1-A')->first()->is_active)->toBeTrue();
});

// ---------------------------------------------------------------------------
// Media security (ProductMediaExtractor)
// ---------------------------------------------------------------------------

it('rejects a zip-slip path entry', function () {
    $zip = bi3Zip(['../evil.png' => bi3PngBytes()]);
    $warnings = [];
    expect(fn () => (new ProductMediaExtractor)->extract($zip, $warnings))->toThrow(MediaImportException::class);
    unlink($zip);
});

it('rejects an oversized zip', function () {
    config(['import.media.max_zip_size' => 10]); // 10 bytes
    $zip = bi3Zip(['a.png' => bi3PngBytes()]);
    $warnings = [];
    expect(fn () => (new ProductMediaExtractor)->extract($zip, $warnings))->toThrow(MediaImportException::class);
    unlink($zip);
});

it('skips a fake-extension image with a warning', function () {
    $zip = bi3Zip(['fake.png' => 'this is plain text, not a png']);
    $warnings = [];
    $result = (new ProductMediaExtractor)->extract($zip, $warnings);
    unlink($zip);

    expect($result['files'])->toBeEmpty()
        ->and($warnings)->not->toBeEmpty();

    // cleanup temp dir
    @array_map('unlink', (array) glob($result['dir'].'/*'));
    @rmdir($result['dir']);
});

// ---------------------------------------------------------------------------
// Media: listed image missing from ZIP → warning, product still imported
// ---------------------------------------------------------------------------

it('warns when a listed image is missing but still imports the product', function () {
    bi3Brand();
    bi3Category();

    // Product lists an image but there is a media file present (so it is not forced to draft logic path): supply an unrelated image.
    $mediaFiles = ['other.png' => ['path' => '/nonexistent', 'ext' => 'png', 'type' => 'image']];
    $path = bi3Xlsx([
        ['sku' => 'IMG-1', 'name_ar' => 'منتج', 'name_en' => 'Product', 'brand' => 'BI3Brand', 'category' => 'إضاءة > داخلية > داون لايت', 'status' => 'منشور', 'price' => '50', 'stock_qty' => '1', 'images' => 'missing.jpg'],
    ]);
    [$counts, $warnings] = bi3Apply($path, $mediaFiles);
    unlink($path);

    expect(Product::where('sku', 'IMG-1')->exists())->toBeTrue()
        ->and($counts['created'])->toBe(1)
        ->and(collect($warnings)->contains(fn ($w) => str_contains($w['message'], 'missing.jpg')))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Media: NEW product with zero valid images → forced to draft
// ---------------------------------------------------------------------------

it('forces a new product with zero valid images to draft', function () {
    bi3Brand();
    bi3Category();

    $path = bi3Xlsx([
        ['sku' => 'DRAFT-1', 'name_ar' => 'منتج', 'name_en' => 'Product', 'brand' => 'BI3Brand', 'category' => 'إضاءة > داخلية > داون لايت', 'status' => 'منشور', 'price' => '50', 'stock_qty' => '1'],
    ]);
    [$counts, $warnings] = bi3Apply($path); // no ZIP at all
    unlink($path);

    $product = Product::where('sku', 'DRAFT-1')->first();
    expect($product)->not->toBeNull()
        ->and($product->status->value)->toBe('draft')
        ->and(collect($warnings)->contains(fn ($w) => str_contains($w['message'], 'DRAFT-1')))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Overview: <script> stripped
// ---------------------------------------------------------------------------

it('strips script tags from overview via the purifier', function () {
    bi3Brand();
    bi3Category();

    $path = bi3Xlsx([
        ['sku' => 'OV-1', 'name_ar' => 'منتج', 'name_en' => 'Product', 'brand' => 'BI3Brand', 'category' => 'إضاءة > داخلية > داون لايت', 'price' => '50', 'stock_qty' => '1', 'overview_ar' => '<script>alert(1)</script>سطر', 'overview_en' => 'line one'],
    ]);
    bi3Apply($path);
    unlink($path);

    $overview = Product::where('sku', 'OV-1')->first()->getTranslation('overview', 'ar');
    expect($overview)->not->toContain('<script>');
});

// ---------------------------------------------------------------------------
// Cache: flushed after apply (public visibility)
// ---------------------------------------------------------------------------

it('flushes caches and recomputes price so public data is current', function () {
    $brand = bi3Brand();
    $cat = bi3Category();
    $product = Product::create(['sku' => 'CACHE-1', 'name' => ['ar' => 'منتج', 'en' => 'Product'], 'slug' => ['ar' => 'cache-ar', 'en' => 'cache-en'], 'brand_id' => $brand->id, 'category_id' => $cat->id, 'status' => 'active', 'published_at' => now()]);
    $product->variants()->create(['sku' => 'CACHE-1', 'price' => 100, 'stock_qty' => 5, 'is_default' => true, 'is_active' => true]);

    // Prime a tagged listing cache entry (flushed on apply) — works on both
    // taggable (array/redis) and non-taggable (database/file) stores.
    CacheTags::remember(CacheTags::LISTINGS, 'probe-key', 600, fn () => 'cached');

    $path = bi3Xlsx([
        ['sku' => 'CACHE-1', 'name_ar' => 'منتج', 'name_en' => 'Product', 'brand' => 'BI3Brand', 'category' => 'إضاءة > داخلية > داون لايت', 'price' => '250', 'stock_qty' => '5'],
    ]);
    bi3Apply($path);
    unlink($path);

    expect(CacheTags::remember(CacheTags::LISTINGS, 'probe-key', 600, fn () => 'fresh'))->toBe('fresh')
        ->and((float) $product->fresh()->min_price)->toBe(250.0);
});

// ---------------------------------------------------------------------------
// Round-trip: export → re-import = zero changes
// ---------------------------------------------------------------------------

it('re-imports an export with zero changes', function () {
    $brand = bi3Brand();
    $cat = bi3Category();
    $attr = bi3Attr();

    // Simple product.
    $simple = Product::create(['sku' => 'RT-S', 'name' => ['ar' => 'بسيط', 'en' => 'Simple'], 'slug' => ['ar' => 'rt-s-ar', 'en' => 'rt-s-en'], 'brand_id' => $brand->id, 'category_id' => $cat->id, 'status' => 'active', 'published_at' => now()]);
    $simple->variants()->create(['sku' => 'RT-S', 'price' => 100, 'stock_qty' => 5, 'is_default' => true, 'is_active' => true]);
    $simple->specs()->create(['name' => ['ar' => 'الوزن', 'en' => 'Weight'], 'value' => ['ar' => '1 كجم', 'en' => '1 kg'], 'sort_order' => 0]);
    app(UpdateProductCacheColumns::class)->handle($simple->refresh());

    // Variant product.
    $vp = Product::create(['sku' => 'RT-V', 'name' => ['ar' => 'متعدد', 'en' => 'Variant'], 'slug' => ['ar' => 'rt-v-ar', 'en' => 'rt-v-en'], 'brand_id' => $brand->id, 'category_id' => $cat->id, 'status' => 'active', 'published_at' => now()]);
    $a = $vp->variants()->create(['sku' => 'RT-V-A', 'price' => 200, 'stock_qty' => 3, 'is_default' => true, 'is_active' => true]);
    $a->attributeValues()->sync([$attr->values->firstWhere('value', '2700')->id]);
    $b = $vp->variants()->create(['sku' => 'RT-V-B', 'price' => 220, 'stock_qty' => 4, 'is_active' => true]);
    $b->attributeValues()->sync([$attr->values->firstWhere('value', '3000')->id]);
    app(UpdateProductCacheColumns::class)->handle($vp->refresh());

    $productsBefore = Product::count();
    $variantsBefore = ProductVariant::count();
    $specsBefore = ProductSpec::count();

    // Export → temp xlsx → re-import.
    $ss = (new ProductExportBuilder)->build();
    $path = tempnam(sys_get_temp_dir(), 'dots_rt_').'.xlsx';
    (new Xlsx($ss))->save($path);
    [$counts] = bi3Apply($path);
    unlink($path);

    expect(Product::count())->toBe($productsBefore)
        ->and(ProductVariant::where('is_active', true)->count())->toBe($variantsBefore)
        ->and(ProductSpec::count())->toBe($specsBefore)
        ->and($counts['created'])->toBe(0)
        ->and((float) $simple->variants()->where('sku', 'RT-S')->first()->price)->toBe(100.0)
        ->and((float) $vp->variants()->where('sku', 'RT-V-A')->first()->price)->toBe(200.0);
});

// ---------------------------------------------------------------------------
// Full flow: attach real images from a ZIP
// ---------------------------------------------------------------------------

it('attaches gallery images from a ZIP after apply', function () {
    bi3Brand();
    bi3Category();

    $dir = storage_path('app/private/import-media/'.bin2hex(random_bytes(6)));
    mkdir($dir, 0775, true);
    $imgPath = $dir.'/img-1.png';
    file_put_contents($imgPath, bi3PngBytes());
    $mediaFiles = ['img-1.png' => ['path' => $imgPath, 'ext' => 'png', 'type' => 'image']];

    $path = bi3Xlsx([
        ['sku' => 'GAL-1', 'name_ar' => 'منتج', 'name_en' => 'Product', 'brand' => 'BI3Brand', 'category' => 'إضاءة > داخلية > داون لايت', 'status' => 'منشور', 'price' => '50', 'stock_qty' => '1', 'images' => 'img-1.png'],
    ]);
    [$counts] = bi3Apply($path, $mediaFiles);
    unlink($path);

    $product = Product::where('sku', 'GAL-1')->first();
    expect($counts['images'])->toBe(1)
        ->and($product->getMedia('gallery'))->toHaveCount(1)
        ->and($product->status->value)->toBe('active'); // had a valid image → not forced draft

    @array_map('unlink', (array) glob($dir.'/*'));
    @rmdir($dir);
});

// ---------------------------------------------------------------------------
// Livewire: authorization + dispatch
// ---------------------------------------------------------------------------

it('apply() returns 403 without products.import permission', function () {
    loginAdmin(['dashboard.view']);
    Livewire::test(Import::class)->call('apply')->assertForbidden();
});

it('apply dispatches the job and records an import log', function () {
    bi3Brand();
    bi3Category();

    $path = bi3Xlsx([
        ['sku' => 'LW-1', 'name_ar' => 'منتج', 'name_en' => 'Product', 'brand' => 'BI3Brand', 'category' => 'إضاءة > داخلية > داون لايت', 'status' => 'مسودة', 'price' => '50', 'stock_qty' => '1'],
    ]);

    $upload = UploadedFile::fake()->createWithContent('import.xlsx', (string) file_get_contents($path));
    unlink($path);

    Livewire::test(Import::class)
        ->set('xlsxFile', $upload)
        ->call('apply')
        ->assertSet('applied', true);

    expect(ImportLog::where('status', 'completed')->count())->toBe(1)
        ->and(Product::where('sku', 'LW-1')->exists())->toBeTrue();
});

it('refreshStatus sets applyStalled after 30s with a pending log', function () {
    $log = ImportLog::create([
        'user_id' => auth('admin')->id(),
        'filename' => 'test.xlsx',
        'status' => 'pending',
    ]);

    Livewire::test(Import::class)
        ->set('importLogId', $log->id)
        ->set('applying', true)
        ->set('applyDispatchedAt', time() - 35)
        ->call('refreshStatus')
        ->assertSet('applyStalled', true);
});
