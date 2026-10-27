<?php

use App\Livewire\Admin\Products\Import;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductExportBuilder;
use App\Services\ProductTemplateBuilder;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

beforeEach(fn () => loginAdmin());

function importBrand(string $name): Brand
{
    return Brand::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
}

/** Creates a 3-level active category and returns the level-3 instance. */
function importCategory(string $l1Ar, string $l2Ar, string $l3Ar): Category
{
    $makeSlug = fn (string $s) => Str::slug($s) ?: 'cat-'.uniqid();

    $l1 = Category::where('level', 1)->where('name->ar', $l1Ar)->first()
        ?? Category::create(['level' => 1, 'is_active' => true, 'name' => ['ar' => $l1Ar, 'en' => $l1Ar], 'slug' => ['ar' => $makeSlug($l1Ar), 'en' => $makeSlug($l1Ar)]]);

    $l2 = Category::where('level', 2)->where('name->ar', $l2Ar)->where('parent_id', $l1->id)->first()
        ?? Category::create(['level' => 2, 'parent_id' => $l1->id, 'is_active' => true, 'name' => ['ar' => $l2Ar, 'en' => $l2Ar], 'slug' => ['ar' => $makeSlug($l2Ar), 'en' => $makeSlug($l2Ar)]]);

    return Category::where('level', 3)->where('name->ar', $l3Ar)->where('parent_id', $l2->id)->first()
        ?? Category::create(['level' => 3, 'parent_id' => $l2->id, 'is_active' => true, 'name' => ['ar' => $l3Ar, 'en' => $l3Ar], 'slug' => ['ar' => $makeSlug($l3Ar), 'en' => $makeSlug($l3Ar)]]);
}

/** Creates an is_variant_axis Attribute with two values. */
function importAxisAttr(string $code, string $nameAr, array $values): Attribute
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

// ---------------------------------------------------------------------------
// Page access
// ---------------------------------------------------------------------------

it('gates the import page behind products.import permission', function () {
    loginAdmin(['dashboard.view']);
    $this->get(route('admin.products.import'))->assertForbidden();

    loginAdmin(['products.import']);
    $this->get(route('admin.products.import'))->assertOk();
});

it('hides download-template button without products.import permission', function () {
    loginAdmin(['products.export']); // export only, no import
    Livewire::test(Import::class)
        ->assertDontSee(__('admin.import_products.download_template'));
});

it('hides export button without products.export permission', function () {
    loginAdmin(['products.import']); // import only, no export
    Livewire::test(Import::class)
        ->assertDontSee(__('admin.import_products.export'));
});

it('shows both buttons to admin with both permissions', function () {
    Livewire::test(Import::class)
        ->assertSee(__('admin.import_products.download_template'))
        ->assertSee(__('admin.import_products.export'));
});

// ---------------------------------------------------------------------------
// downloadTemplate — authorization
// ---------------------------------------------------------------------------

it('downloadTemplate returns 403 without products.import permission', function () {
    loginAdmin(['dashboard.view']);
    Livewire::test(Import::class)->call('downloadTemplate')->assertForbidden();
});

// ---------------------------------------------------------------------------
// Template generator — live dropdown data
// ---------------------------------------------------------------------------

it('template brands sheet contains seeded brands', function () {
    importBrand('فيليبس');
    importBrand('سيمنز');

    $spreadsheet = (new ProductTemplateBuilder)->build();
    $brandsSheet = $spreadsheet->getSheetByName('البراندات');
    expect($brandsSheet)->not->toBeNull();

    $values = collect(range(2, 100))
        ->map(fn ($r) => (string) $brandsSheet->getCell('A'.$r)->getValue())
        ->filter(fn ($v) => $v !== '')
        ->values()
        ->all();

    expect($values)->toContain('فيليبس')
        ->and($values)->toContain('سيمنز');
});

it('template categories sheet contains seeded level-3 paths', function () {
    importCategory('إضاءة', 'داخلية', 'داون لايت');

    $spreadsheet = (new ProductTemplateBuilder)->build();
    $catSheet = $spreadsheet->getSheetByName('الأقسام');
    expect($catSheet)->not->toBeNull();

    $values = collect(range(2, 200))
        ->map(fn ($r) => (string) $catSheet->getCell('A'.$r)->getValue())
        ->filter(fn ($v) => $v !== '')
        ->values()
        ->all();

    expect($values)->toContain('إضاءة > داخلية > داون لايت');
});

it('template attr-values sheet contains seeded axis attribute values', function () {
    importAxisAttr('cct', 'درجة اللون', ['3000K', '4000K', '6500K']);

    $spreadsheet = (new ProductTemplateBuilder)->build();
    $attrSheet = $spreadsheet->getSheetByName('قيم الاختيارات');
    expect($attrSheet)->not->toBeNull();

    // Find the column whose row-2 code is 'cct'
    $cctCol = null;
    for ($i = 1; $i <= 26; $i++) {
        $col = Coordinate::stringFromColumnIndex($i);
        $code = (string) $attrSheet->getCell($col.'2')->getValue();
        if ($code === 'cct') {
            $cctCol = $col;
            break;
        }
    }

    expect($cctCol)->not->toBeNull('cct column not found in قيم الاختيارات');

    $values = collect(range(3, 20))
        ->map(fn ($r) => (string) $attrSheet->getCell($cctCol.$r)->getValue())
        ->filter(fn ($v) => $v !== '')
        ->values()
        ->all();

    expect($values)->toContain('3000K')
        ->and($values)->toContain('4000K')
        ->and($values)->toContain('6500K');
});

it('template variants sheet has attr:<code> columns for axis attributes', function () {
    importAxisAttr('wattage', 'القدرة', ['10W', '20W']);

    $spreadsheet = (new ProductTemplateBuilder)->build();
    $variantsSheet = $spreadsheet->getSheetByName('الاختيارات');
    expect($variantsSheet)->not->toBeNull();

    // Read key row
    $keys = [];
    for ($i = 1; $i <= 30; $i++) {
        $col = Coordinate::stringFromColumnIndex($i);
        $k = (string) $variantsSheet->getCell($col.'2')->getValue();
        if ($k !== '') {
            $keys[] = $k;
        }
    }

    expect($keys)->toContain('attr:wattage');
});

// ---------------------------------------------------------------------------
// Template — no leading = in DataValidation formulas
// ---------------------------------------------------------------------------

it('every DataValidation formula on the products sheet does not start with =', function () {
    $spreadsheet = (new ProductTemplateBuilder)->build();
    $productsSheet = $spreadsheet->getSheetByName('المنتجات');
    expect($productsSheet)->not->toBeNull();

    $validations = $productsSheet->getDataValidationCollection();

    foreach ($validations as $coord => $validation) {
        $f1 = $validation->getFormula1();
        expect($f1)->not->toStartWith('=', "DataValidation formula at {$coord} starts with '=': {$f1}");
    }
});

it('every DataValidation formula on the variants sheet does not start with =', function () {
    importAxisAttr('finish', 'التشطيب', ['أبيض', 'أسود']);

    $spreadsheet = (new ProductTemplateBuilder)->build();
    $variantsSheet = $spreadsheet->getSheetByName('الاختيارات');
    expect($variantsSheet)->not->toBeNull();

    foreach ($variantsSheet->getDataValidationCollection() as $coord => $validation) {
        $f1 = $validation->getFormula1();
        expect($f1)->not->toStartWith('=', "DataValidation formula at {$coord} starts with '=': {$f1}");
    }
});

// ---------------------------------------------------------------------------
// Export — formula-injection escaping
// ---------------------------------------------------------------------------

it('export escapes cells whose value starts with =', function () {
    $brand = importBrand('TestBrand');
    $category = importCategory('قسم', 'فرعي', 'ثلاثي');

    $product = Product::create([
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'sku' => 'INJECT-1',
        'name' => ['ar' => '=1+1', 'en' => '+bad'],
        'slug' => ['ar' => 'inject-ar', 'en' => 'inject-en'],
        'status' => 'active',
        'stock_status' => 'in',
    ]);
    $product->variants()->create([
        'sku' => 'INJECT-1-V',
        'price' => 10,
        'stock_qty' => 1,
        'is_default' => true,
    ]);

    $spreadsheet = (new ProductExportBuilder)->build();
    $productsSheet = $spreadsheet->getSheetByName('المنتجات');
    expect($productsSheet)->not->toBeNull();

    // Find SKU row
    $skuCol = null;
    $nameArCol = null;
    $nameEnCol = null;
    for ($i = 1; $i <= 30; $i++) {
        $col = Coordinate::stringFromColumnIndex($i);
        $k = (string) $productsSheet->getCell($col.'2')->getValue();
        if ($k === 'sku') {
            $skuCol = $col;
        }
        if ($k === 'name_ar') {
            $nameArCol = $col;
        }
        if ($k === 'name_en') {
            $nameEnCol = $col;
        }
    }

    expect($skuCol)->not->toBeNull();

    $dataRow = null;
    for ($r = 3; $r <= 200; $r++) {
        if ($productsSheet->getCell($skuCol.$r)->getValue() === 'INJECT-1') {
            $dataRow = $r;
            break;
        }
    }

    expect($dataRow)->not->toBeNull('Product row not found in export');
    expect($productsSheet->getCell($nameArCol.$dataRow)->getValue())->toBe("'=1+1");
    expect($productsSheet->getCell($nameEnCol.$dataRow)->getValue())->toBe("'+bad");
});

// ---------------------------------------------------------------------------
// Authorization for downloadTemplate / export actions
// ---------------------------------------------------------------------------

it('export action returns 403 without products.export permission', function () {
    loginAdmin(['products.import']);
    Livewire::test(Import::class)->call('export')->assertForbidden();
});
