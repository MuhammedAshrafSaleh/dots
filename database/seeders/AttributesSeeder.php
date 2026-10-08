<?php

namespace Database\Seeders;

use App\Enums\AttributeDisplay;
use App\Models\Attribute;
use Illuminate\Database\Seeder;

class AttributesSeeder extends Seeder
{
    public function run(): void
    {
        // Colour temperature (K_TEMPS / K_FACETS.temps) — swatch chips.
        $cct = Attribute::updateOrCreate(['code' => 'cct'], [
            'name' => ['en' => 'Colour temperature', 'ar' => 'درجة اللون'],
            'display' => AttributeDisplay::Swatch, 'unit' => 'K',
            'is_filterable' => true, 'is_variant_axis' => true, 'sort_order' => 1,
        ]);
        $this->values($cct, [
            ['2700', ['en' => 'Warm', 'ar' => 'دافئ'], '#FFD08A'],
            ['3000', ['en' => 'Warm white', 'ar' => 'أبيض دافئ'], '#FFE2B0'],
            ['4000', ['en' => 'Neutral', 'ar' => 'محايد'], '#FFF6E6'],
            ['6500', ['en' => 'Daylight', 'ar' => 'نهاري'], '#E6EEFF'],
        ]);

        // Wattage (K_FACETS.watts) — buttons.
        $wattage = Attribute::updateOrCreate(['code' => 'wattage'], [
            'name' => ['en' => 'Wattage', 'ar' => 'القدرة'],
            'display' => AttributeDisplay::Button, 'unit' => 'W',
            'is_filterable' => true, 'is_variant_axis' => true, 'sort_order' => 2,
        ]);
        $this->values($wattage, array_map(
            fn (string $w) => [$w, ['en' => $w.'W', 'ar' => $w.' وات'], null],
            ['5', '7', '10', '12', '18', '24', '36'],
        ));

        // Size — buttons (representative values; the design shows Ø / mm sizes).
        $size = Attribute::updateOrCreate(['code' => 'size'], [
            'name' => ['en' => 'Size', 'ar' => 'المقاس'],
            'display' => AttributeDisplay::Button, 'unit' => 'mm',
            'is_filterable' => true, 'is_variant_axis' => true, 'sort_order' => 3,
        ]);
        $this->values($size, [
            ['300', ['en' => 'Ø300', 'ar' => 'Ø300'], null],
            ['450', ['en' => 'Ø450', 'ar' => 'Ø450'], null],
            ['600', ['en' => '600mm', 'ar' => '600مم'], null],
            ['1200', ['en' => '1200mm', 'ar' => '1200مم'], null],
        ]);

        // Finish (K_FACETS.finishes / K_FINISHES) — swatches.
        $finish = Attribute::updateOrCreate(['code' => 'finish'], [
            'name' => ['en' => 'Finish', 'ar' => 'التشطيب'],
            'display' => AttributeDisplay::Swatch, 'unit' => null,
            'is_filterable' => true, 'is_variant_axis' => true, 'sort_order' => 4,
        ]);
        $this->values($finish, [
            ['black', ['en' => 'Matte black', 'ar' => 'أسود مطفي'], '#1E1E1C'],
            ['white', ['en' => 'White', 'ar' => 'أبيض'], '#F4F3EF'],
            ['brass', ['en' => 'Brass', 'ar' => 'نحاسي'], 'linear-gradient(135deg,#C9A45C,#E8CF96 50%,#B08A45)'],
            ['chrome', ['en' => 'Chrome', 'ar' => 'كروم'], 'linear-gradient(135deg,#9FA3A8,#EEF0F2 50%,#8C9095)'],
        ]);
    }

    /** @param  array<int, array{0: string, 1: array<string, string>, 2: string|null}>  $rows */
    private function values(Attribute $attribute, array $rows): void
    {
        foreach ($rows as $i => [$value, $label, $swatch]) {
            $attribute->values()->updateOrCreate(
                ['value' => $value],
                ['label' => $label, 'swatch' => $swatch, 'sort_order' => $i],
            );
        }
    }
}
