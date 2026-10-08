<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Enums\StockStatus;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Generates ~10k products for EXPLAIN / performance testing only (never part of
 * the default seed). Events are disabled and cache columns are set inline, so
 * it stays fast and does not hammer the observers.
 */
class LargeCatalogSeeder extends Seeder
{
    private const COUNT = 10000;

    public function run(): void
    {
        $leaves = Category::where('level', 3)->pluck('id')->all();
        $brands = Brand::pluck('id', 'name')->all();
        $cctValues = Attribute::where('code', 'cct')->first()?->values()->pluck('id')->all() ?? [];
        $finishValues = Attribute::where('code', 'finish')->first()?->values()->pluck('id')->all() ?? [];

        if ($leaves === [] || $brands === []) {
            $this->command->warn('Run the catalog seeders first (categories, brands, attributes).');

            return;
        }

        $brandNames = array_keys($brands);
        $stockStates = [StockStatus::In, StockStatus::Low, StockStatus::Order];

        Model::withoutEvents(function () use ($leaves, $brands, $brandNames, $cctValues, $finishValues, $stockStates): void {
            for ($start = 1; $start <= self::COUNT; $start += 500) {
                DB::transaction(function () use ($start, $leaves, $brands, $brandNames, $cctValues, $finishValues, $stockStates): void {
                    for ($i = $start; $i < $start + 500 && $i <= self::COUNT; $i++) {
                        $brandName = $brandNames[array_rand($brandNames)];
                        $price = random_int(100, 9000);
                        $stock = $stockStates[array_rand($stockStates)];
                        $sku = 'LC-'.$i;

                        $product = Product::create([
                            'brand_id' => $brands[$brandName],
                            'category_id' => $leaves[array_rand($leaves)],
                            'name' => ['en' => "Perf product {$i}", 'ar' => "منتج اختبار {$i}"],
                            'slug' => ['en' => "perf-product-{$i}", 'ar' => "منتج-اختبار-{$i}"],
                            'sku' => $sku,
                            'status' => ProductStatus::Active,
                            'published_at' => now(),
                            'min_price' => $price,
                            'max_compare_at' => $price + 200,
                            'stock_status' => $stock->value,
                            'search_text' => "Perf product {$i} منتج اختبار {$i} {$sku} {$brandName}",
                            'sort_weight' => random_int(0, 100),
                        ]);

                        $variant = $product->variants()->create([
                            'sku' => $sku.'-1',
                            'price' => $price,
                            'compare_at_price' => $price + 200,
                            'stock_qty' => $stock === StockStatus::Order ? 0 : random_int(1, 50),
                            'is_default' => true,
                            'is_active' => true,
                        ]);

                        $values = array_filter([
                            $cctValues ? $cctValues[array_rand($cctValues)] : null,
                            $finishValues ? $finishValues[array_rand($finishValues)] : null,
                        ]);
                        if ($values !== []) {
                            $variant->attributeValues()->attach($values);
                        }
                    }
                });

                $this->command->info('Seeded '.min($start + 499, self::COUNT).' / '.self::COUNT);
            }
        });
    }
}
