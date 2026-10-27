<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * The one bulk-import browser E2E (IMPORT_SPEC / BI.3): download template is
 * covered elsewhere — here we fill 2 products, upload the xlsx + media ZIP,
 * preview, apply, and confirm both products are live on the public site WITH
 * images. Run with `composer test:browser` (owner-only; browser tests paused).
 */
function importSignInAdmin()
{
    $admin = User::firstWhere('email', 'e2e@dots.test') ?? adminUser();
    $admin->forceFill(['email' => 'e2e@dots.test'])->save();

    return visit('/admin/lang/en')
        ->navigate('/admin/login')
        ->fill('input[type=email]', 'e2e@dots.test')
        ->fill('input[type=password]', 'password')
        ->click('button[type=submit]')
        ->assertSee('Dashboard')
        ->assertNoJavaScriptErrors();
}

function importE2EPng(): string
{
    $img = imagecreatetruecolor(32, 32);
    imagefill($img, 0, 0, imagecolorallocate($img, 200, 120, 40));
    ob_start();
    imagepng($img);
    $bytes = (string) ob_get_clean();
    imagedestroy($img);

    return $bytes;
}

it('imports two products with images and shows them live on the site', function () {
    $brand = Brand::create(['name' => 'ImpBrand', 'slug' => 'imp-brand']);
    $l1 = Category::create(['level' => 1, 'is_active' => true, 'name' => ['ar' => 'إضاءة', 'en' => 'Lighting'], 'slug' => ['ar' => 'اضاءه-e2e', 'en' => 'lighting-e2e']]);
    $l2 = Category::create(['level' => 2, 'parent_id' => $l1->id, 'is_active' => true, 'name' => ['ar' => 'داخلية', 'en' => 'Indoor'], 'slug' => ['ar' => 'داخليه-e2e', 'en' => 'indoor-e2e']]);
    Category::create(['level' => 3, 'parent_id' => $l2->id, 'is_active' => true, 'name' => ['ar' => 'داون لايت', 'en' => 'Downlight'], 'slug' => ['ar' => 'داون-لايت-e2e', 'en' => 'downlight-e2e']]);

    $dir = storage_path('app/private/e2e-import-'.uniqid());
    mkdir($dir, 0775, true);

    // --- xlsx with two simple products ---
    $ss = new Spreadsheet;
    $ss->removeSheetByIndex(0);
    $keys = ['sku', 'name_ar', 'name_en', 'brand', 'category', 'status', 'price', 'stock_qty', 'images'];
    $rows = [
        ['sku' => 'E2E-1', 'name_ar' => 'منتج واحد', 'name_en' => 'Import One', 'brand' => 'ImpBrand', 'category' => 'إضاءة > داخلية > داون لايت', 'status' => 'منشور', 'price' => '120', 'stock_qty' => '5', 'images' => 'E2E-1.png'],
        ['sku' => 'E2E-2', 'name_ar' => 'منتج اثنان', 'name_en' => 'Import Two', 'brand' => 'ImpBrand', 'category' => 'إضاءة > داخلية > داون لايت', 'status' => 'منشور', 'price' => '240', 'stock_qty' => '8', 'images' => 'E2E-2.png'],
    ];
    foreach (['المنتجات' => $keys, 'الاختيارات' => ['product_sku', 'variant_sku'], 'المواصفات' => ['product_sku', 'name_ar', 'name_en', 'value_ar', 'value_en']] as $title => $sheetKeys) {
        $sheet = new Worksheet($ss, $title);
        $ss->addSheet($sheet);
        foreach ($sheetKeys as $i => $key) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).'2', $key);
        }
    }
    $products = $ss->getSheetByName('المنتجات');
    foreach ($rows as $j => $row) {
        foreach ($keys as $i => $key) {
            $products->setCellValue(Coordinate::stringFromColumnIndex($i + 1).($j + 3), $row[$key]);
        }
    }
    $xlsxPath = $dir.'/import.xlsx';
    (new Xlsx($ss))->save($xlsxPath);

    // --- media ZIP ---
    $zipPath = $dir.'/media.zip';
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('E2E-1.png', importE2EPng());
    $zip->addFromString('E2E-2.png', importE2EPng());
    $zip->close();

    importSignInAdmin()
        ->navigate('/admin/products/import')
        ->assertSee('Upload file')
        ->attach('input[wire:model="xlsxFile"]', $xlsxPath)
        ->attach('input[wire:model="zipFile"]', $zipPath)
        ->click('Preview')
        ->assertSee('New products')
        ->click('Apply valid rows')
        ->waitForText('Import complete')
        ->assertNoJavaScriptErrors();

    // Both products are live on the public site, with an image.
    visit('/products/import-one')
        ->assertSee('Import One')
        ->assertPresent('img')
        ->assertNoJavaScriptErrors();

    visit('/products/import-two')
        ->assertSee('Import Two')
        ->assertPresent('img');

    // cleanup
    @array_map('unlink', (array) glob($dir.'/*'));
    @rmdir($dir);
});
