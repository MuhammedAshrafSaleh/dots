<?php

namespace Database\Seeders;

use App\Enums\ProductRelationType;
use App\Enums\ProductStatus;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\Slug;
use Database\Seeders\Concerns\SeedsDesignMedia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    use SeedsDesignMedia;

    /**
     * Leaf categories keyed by English name, filled while building the tree.
     *
     * @var array<string, Category>
     */
    private array $leaves = [];

    public function run(): void
    {
        $this->seedCategories();
        $this->seedBrands();
        $this->seedProducts();
    }

    private function seedCategories(): void
    {
        // DOTS_MEGA_DEFAULT (MegaMenu.jsx): root (icon) → group → leaf link.
        $tree = [
            ['indoor', 'lamp-ceiling', ['en' => 'Indoor Lighting', 'ar' => 'إضاءة داخلية'], [
                [['en' => 'Ceiling', 'ar' => 'أسقف'], [
                    ['en' => 'Pendants', 'ar' => 'معلقات'], ['en' => 'Chandeliers', 'ar' => 'نجف'],
                    ['en' => 'Downlights', 'ar' => 'سبوتات غاطسة'], ['en' => 'Panel lights', 'ar' => 'بانلات'],
                    ['en' => 'Track lighting', 'ar' => 'تراك لايت'],
                ]],
                [['en' => 'Wall & floor', 'ar' => 'حوائط وأرضيات'], [
                    ['en' => 'Wall lights', 'ar' => 'أباليك'], ['en' => 'Floor lamps', 'ar' => 'أباجورات أرضية'],
                    ['en' => 'Table lamps', 'ar' => 'أباجورات مكتب'], ['en' => 'Step lights', 'ar' => 'إضاءة سلالم'],
                ]],
                [['en' => 'Linear & strips', 'ar' => 'خطية وشرائط'], [
                    ['en' => 'LED strips', 'ar' => 'شرائط LED'], ['en' => 'Aluminium profiles', 'ar' => 'بروفايلات ألومنيوم'],
                    ['en' => 'Drivers', 'ar' => 'محولات'], ['en' => 'Linear systems', 'ar' => 'أنظمة خطية'],
                ]],
            ]],
            ['outdoor', 'sun', ['en' => 'Outdoor Lighting', 'ar' => 'إضاءة خارجية'], [
                [['en' => 'Facade', 'ar' => 'واجهات'], [
                    ['en' => 'Wall washers', 'ar' => 'وول واشر'], ['en' => 'Up/down lights', 'ar' => 'إضاءة أعلى/أسفل'],
                ]],
                [['en' => 'Landscape', 'ar' => 'لاندسكيب'], [
                    ['en' => 'Bollards', 'ar' => 'أعمدة قصيرة'], ['en' => 'Spike lights', 'ar' => 'كشافات أرضية'],
                    ['en' => 'In-ground', 'ar' => 'مدفونة'],
                ]],
                [['en' => 'Area', 'ar' => 'مساحات'], [
                    ['en' => 'Floodlights', 'ar' => 'كشافات'], ['en' => 'Street lights', 'ar' => 'أعمدة إنارة'],
                ]],
            ]],
            ['smart', 'wifi', ['en' => 'Smart Home', 'ar' => 'المنزل الذكي'], [
                [['en' => 'Control', 'ar' => 'التحكم'], [
                    ['en' => 'Smart switches', 'ar' => 'مفاتيح ذكية'], ['en' => 'Dimmers', 'ar' => 'ديمر'],
                    ['en' => 'Scene panels', 'ar' => 'لوحات مشاهد'], ['en' => 'Hubs & gateways', 'ar' => 'بوابات'],
                ]],
                [['en' => 'Sensors', 'ar' => 'حساسات'], [
                    ['en' => 'Motion', 'ar' => 'حركة'], ['en' => 'Door & window', 'ar' => 'أبواب ونوافذ'],
                    ['en' => 'Climate', 'ar' => 'مناخ'],
                ]],
                [['en' => 'Comfort', 'ar' => 'الراحة'], [
                    ['en' => 'Curtain motors', 'ar' => 'موتورات ستائر'], ['en' => 'Thermostats', 'ar' => 'ثرموستات'],
                    ['en' => 'Smart bulbs', 'ar' => 'لمبات ذكية'],
                ]],
            ]],
            ['electrical', 'plug-zap', ['en' => 'Electrical', 'ar' => 'مستلزمات كهربائية'], [
                [['en' => 'Wiring devices', 'ar' => 'مفاتيح وبرايز'], [
                    ['en' => 'Switches', 'ar' => 'مفاتيح'], ['en' => 'Sockets', 'ar' => 'برايز'], ['en' => 'USB outlets', 'ar' => 'مخارج USB'],
                ]],
                [['en' => 'Distribution', 'ar' => 'توزيع'], [
                    ['en' => 'Circuit breakers', 'ar' => 'قواطع'], ['en' => 'Distribution boards', 'ar' => 'لوحات توزيع'],
                    ['en' => 'RCDs', 'ar' => 'قواطع تسريب'],
                ]],
                [['en' => 'Cables', 'ar' => 'كابلات'], [
                    ['en' => 'Power cables', 'ar' => 'كابلات قوى'], ['en' => 'Conduits', 'ar' => 'مواسير'],
                    ['en' => 'Accessories', 'ar' => 'إكسسوارات'],
                ]],
            ]],
            ['lamps', 'lightbulb', ['en' => 'Lamps & Bulbs', 'ar' => 'لمبات'], [
                [['en' => 'By base', 'ar' => 'حسب القاعدة'], [
                    ['en' => 'E27', 'ar' => 'E27'], ['en' => 'E14', 'ar' => 'E14'], ['en' => 'GU10', 'ar' => 'GU10'], ['en' => 'G9', 'ar' => 'G9'],
                ]],
                [['en' => 'By type', 'ar' => 'حسب النوع'], [
                    ['en' => 'Filament', 'ar' => 'فتيل'], ['en' => 'Tubes', 'ar' => 'ليد تيوب'], ['en' => 'Spots', 'ar' => 'سبوت'],
                ]],
            ]],
        ];

        $rootSort = 0;
        foreach ($tree as [$slug, $icon, $name, $groups]) {
            $root = Category::updateOrCreate(
                ['slug->en' => Slug::make($name['en'], 'en')],
                ['level' => 1, 'name' => $name, 'slug' => Slug::fromTranslations($name),
                    'icon' => $icon, 'sort_order' => $rootSort++, 'is_active' => true],
            );

            $groupSort = 0;
            foreach ($groups as [$groupName, $links]) {
                $group = Category::updateOrCreate(
                    ['parent_id' => $root->id, 'slug->en' => Slug::make($groupName['en'], 'en')],
                    ['level' => 2, 'name' => $groupName, 'slug' => Slug::fromTranslations($groupName),
                        'parent_id' => $root->id, 'sort_order' => $groupSort++, 'is_active' => true],
                );

                $leafSort = 0;
                foreach ($links as $leafName) {
                    $leaf = Category::updateOrCreate(
                        ['parent_id' => $group->id, 'slug->en' => Slug::make($leafName['en'], 'en')],
                        ['level' => 3, 'name' => $leafName, 'slug' => Slug::fromTranslations($leafName),
                            'parent_id' => $group->id, 'sort_order' => $leafSort++, 'is_active' => true],
                    );
                    $this->leaves[$leafName['en']] = $leaf;
                }
            }
        }

        // Home "shop by category" (K_CATEGORIES) — show_on_home + card image.
        $home = [
            ['Pendants', 33], ['Downlights', 14], ['Outdoor Lighting', 4],
            ['Smart Home', 17], ['Electrical', 29], ['LED strips', 2],
        ];
        foreach ($home as [$name, $photo]) {
            $category = $this->leaves[$name] ?? Category::where('name->en', $name)->first();
            if ($category) {
                $category->update(['show_on_home' => true]);
                if (! $category->hasMedia('image')) {
                    $this->attachPhoto($category, $photo, 'image');
                }
            }
        }
    }

    private function seedBrands(): void
    {
        $brands = [
            ['Philips', ['en' => 'Netherlands', 'ar' => 'هولندا'], true],
            ['Legrand', ['en' => 'France', 'ar' => 'فرنسا'], true],
            ['Schneider Electric', ['en' => 'France', 'ar' => 'فرنسا'], true],
            ['Delta Light', ['en' => 'Belgium', 'ar' => 'بلجيكا'], false],
            ['Osram', ['en' => 'Germany', 'ar' => 'ألمانيا'], true],
            ['ABB', ['en' => 'Switzerland', 'ar' => 'سويسرا'], false],
            ['Flos', ['en' => 'Italy', 'ar' => 'إيطاليا'], false],
            ['Artemide', ['en' => 'Italy', 'ar' => 'إيطاليا'], false],
        ];

        foreach ($brands as $i => [$name, $origin, $authorised]) {
            Brand::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'origin_country' => $origin,
                'description' => [
                    'en' => "Genuine {$name} products with official warranty, supplied and specified by DOTS.",
                    'ar' => "منتجات {$name} الأصلية بضمان رسمي، توريد وتوصيف من دوتس.",
                ],
                'is_authorised' => $authorised,
                'is_featured' => $i === 0, // Philips in the brands-page banner
                'show_in_partners' => true,
                'show_on_home' => true,
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }
    }

    private function seedProducts(): void
    {
        // product.cat (data.jsx) → leaf category English name.
        $catMap = [
            'pend' => 'Pendants', 'down' => 'Downlights', 'track' => 'Track lighting',
            'wall' => 'Wall lights', 'smart' => 'Smart switches', 'elec' => 'Sockets', 'strip' => 'LED strips',
        ];
        // product.finishes hex → finish attribute value.
        $finishMap = [
            '#1E1E1C' => 'black', '#4A4A47' => 'black', '#F4F3EF' => 'white',
            '#C9A45C' => 'brass', '#9FA3A8' => 'chrome',
        ];

        $finishAttr = Attribute::where('code', 'finish')->first();
        $cctAttr = Attribute::where('code', 'cct')->first();
        $defaultCct = $cctAttr?->values()->where('value', '3000')->first();

        $products = $this->productData();
        $created = [];

        foreach ($products as $data) {
            $leaf = $this->leaves[$catMap[$data['cat']]] ?? null;
            $brand = Brand::where('name', $data['brand'])->first();
            if (! $leaf || ! $brand) {
                continue;
            }

            $priceOnRequest = ! isset($data['price']);

            $product = Product::updateOrCreate(['sku' => $data['sku']], [
                'brand_id' => $brand->id,
                'category_id' => $leaf->id,
                'name' => $data['name'],
                'slug' => Slug::fromTranslations($data['name']),
                'overview' => [
                    'en' => '<p>'.$data['name']['en'].' — genuine '.$data['brand'].' product, supplied by DOTS.</p>',
                    'ar' => '<p>'.$data['name']['ar'].' — منتج '.$data['brand'].' أصلي، توريد دوتس.</p>',
                ],
                'variants_summary' => $data['variants'],
                'warranty' => ['en' => '2 years', 'ar' => 'سنتان'],
                'price_on_request' => $priceOnRequest,
                'is_new' => $data['new'] ?? false,
                'is_best_seller' => $data['best'] ?? false,
                'is_smart_pick' => $data['smart'] ?? false,
                'is_dimmable' => $data['dimmable'] ?? false,
                'ships_24h' => $data['ships'] ?? false,
                'sort_weight' => $data['weight'] ?? 0,
                'status' => ProductStatus::Active,
                'published_at' => now(),
            ]);

            // Gallery image with bilingual caption (imageLabel).
            if (! $product->hasMedia('gallery')) {
                $this->attachPhoto($product, $data['photo'], 'gallery', ['caption' => $data['label']]);
            }

            // Variants: one per finish (if any), else a single default.
            $stockQty = match ($data['stock']) {
                'in' => 25, 'low' => 3, default => 0
            };
            $finishes = $data['finishes'] ?? [null];
            $product->variants()->delete();

            foreach ($finishes as $fi => $hex) {
                $variant = $product->variants()->create([
                    'sku' => $data['sku'].'-'.($fi + 1),
                    'price' => $priceOnRequest ? null : $data['price'],
                    'compare_at_price' => $data['was'] ?? null,
                    'stock_qty' => $stockQty,
                    'is_default' => $fi === 0,
                    'is_active' => true,
                    'sort_order' => $fi,
                ]);

                $valueIds = [];
                if ($hex !== null && $finishAttr && isset($finishMap[$hex])) {
                    $fv = $finishAttr->values()->where('value', $finishMap[$hex])->first();
                    if ($fv) {
                        $valueIds[] = $fv->id;
                    }
                }
                if ($defaultCct) {
                    $valueIds[] = $defaultCct->id;
                }
                $variant->attributeValues()->sync($valueIds);
            }

            // A couple of flat specs.
            $product->specs()->delete();
            $product->specs()->createMany([
                ['name' => ['en' => 'Brand', 'ar' => 'الماركة'], 'value' => ['en' => $data['brand'], 'ar' => $data['brand']], 'sort_order' => 0],
                ['name' => ['en' => 'Warranty', 'ar' => 'الضمان'], 'value' => ['en' => '2 years', 'ar' => 'سنتان'], 'sort_order' => 1],
            ]);

            $created[$data['id']] = $product;
        }

        // Relations + category featured products.
        if (isset($created['p1'], $created['p2'], $created['p8'])) {
            $created['p1']->relatedProducts()->syncWithPivotValues([$created['p2']->id], ['type' => ProductRelationType::Together->value]);
            $created['p1']->cartSuggestions()->syncWithPivotValues([$created['p8']->id], ['type' => ProductRelationType::CartSuggestion->value]);
        }
        if (isset($created['p1']) && ($pendants = $this->leaves['Pendants'] ?? null)) {
            $pendants->featuredProducts()->syncWithPivotValues([$created['p1']->id], ['sort_order' => 0]);
        }
    }

    /**
     * K_PRODUCTS (data.jsx) + flags/photo (data2.jsx kProdPh).
     *
     * @return array<int, array<string, mixed>>
     */
    private function productData(): array
    {
        return [
            ['id' => 'p1', 'cat' => 'pend', 'brand' => 'Philips', 'sku' => 'PH-ARC-450-30', 'price' => 4850, 'was' => 5700, 'photo' => 33, 'best' => true, 'dimmable' => true, 'ships' => true, 'weight' => 100, 'finishes' => ['#1E1E1C', '#F4F3EF', '#C9A45C'], 'stock' => 'in', 'name' => ['en' => 'Arc LED Pendant Ø450, dimmable', 'ar' => 'معلقة ليد آرك Ø450 قابلة للتعتيم'], 'variants' => ['en' => '3 finishes · 2 CCT', 'ar' => '3 تشطيبات · درجتين'], 'label' => ['en' => 'Cut-out: dome pendant, matte black', 'ar' => 'صورة: معلقة قبة سوداء']],
            ['id' => 'p2', 'cat' => 'down', 'brand' => 'Delta Light', 'sku' => 'DL-TRM-12-40', 'price' => 690, 'photo' => 8, 'new' => true, 'ships' => true, 'weight' => 90, 'stock' => 'in', 'name' => ['en' => 'Trimless Downlight 12W, 36° beam', 'ar' => 'سبوت غاطس بدون إطار 12 وات، 36°'], 'variants' => ['en' => '4 wattages · 3 CCT', 'ar' => '4 قدرات · 3 درجات'], 'label' => ['en' => 'Cut-out: round recessed downlight', 'ar' => 'Cut-out: round recessed downlight']],
            ['id' => 'p3', 'cat' => 'smart', 'brand' => 'Legrand', 'sku' => 'LG-MYH-DIM1-W', 'price' => 1150, 'photo' => 17, 'smart' => true, 'weight' => 80, 'finishes' => ['#F4F3EF', '#1E1E1C'], 'stock' => 'low', 'name' => ['en' => 'Smart Dimmer Switch, 1-gang, glass', 'ar' => 'مفتاح ديمر ذكي، خط واحد، زجاج'], 'variants' => ['en' => 'Works with Alexa & Google', 'ar' => 'يعمل مع أليكسا وجوجل'], 'label' => ['en' => 'Cut-out: glass touch switch', 'ar' => 'Cut-out: glass touch switch']],
            ['id' => 'p4', 'cat' => 'track', 'brand' => 'Osram', 'sku' => 'OS-MAG-10-24', 'price' => 1380, 'photo' => 7, 'ships' => true, 'weight' => 70, 'finishes' => ['#1E1E1C', '#F4F3EF'], 'stock' => 'in', 'name' => ['en' => 'Magnetic Track Spot 10W, 24°', 'ar' => 'سبوت تراك مغناطيسي 10 وات، 24°'], 'variants' => ['en' => '2 finishes', 'ar' => 'تشطيبين'], 'label' => ['en' => 'Cut-out: magnetic track spot', 'ar' => 'Cut-out: magnetic track spot']],
            ['id' => 'p5', 'cat' => 'wall', 'brand' => 'Delta Light', 'sku' => 'DL-UPD-IP65-AN', 'price' => 2240, 'photo' => 4, 'weight' => 60, 'finishes' => ['#4A4A47', '#1E1E1C'], 'stock' => 'in', 'name' => ['en' => 'Up/Down Outdoor Wall Light, IP65', 'ar' => 'أبليك خارجي أعلى/أسفل IP65'], 'variants' => ['en' => 'Anthracite · 3000K', 'ar' => 'أنثراسايت · 3000K'], 'label' => ['en' => 'Cut-out: cylinder wall light', 'ar' => 'Cut-out: cylinder wall light']],
            ['id' => 'p6', 'cat' => 'pend', 'brand' => 'Artemide', 'sku' => 'AR-LIN-1200-D', 'photo' => 28, 'weight' => 50, 'stock' => 'order', 'name' => ['en' => 'Linear Suspension 1200mm, DALI', 'ar' => 'إضاءة معلقة خطية 1200مم، DALI'], 'variants' => ['en' => '3 lengths', 'ar' => '3 أطوال'], 'label' => ['en' => 'Cut-out: linear suspension', 'ar' => 'Cut-out: linear suspension']],
            ['id' => 'p7', 'cat' => 'elec', 'brand' => 'Schneider Electric', 'sku' => 'SE-A9F-1P16C', 'price' => 210, 'photo' => 29, 'ships' => true, 'weight' => 40, 'stock' => 'in', 'name' => ['en' => 'Miniature Circuit Breaker 1P 16A, C-curve', 'ar' => 'قاطع صغير 1P 16A منحنى C'], 'variants' => ['en' => '6–63 A', 'ar' => '6–63 أمبير'], 'label' => ['en' => 'Cut-out: DIN-rail breaker', 'ar' => 'Cut-out: DIN-rail breaker']],
            ['id' => 'p8', 'cat' => 'strip', 'brand' => 'Osram', 'sku' => 'OS-LS-24-144-5', 'price' => 1690, 'was' => 1890, 'photo' => 2, 'best' => true, 'ships' => true, 'weight' => 85, 'stock' => 'in', 'name' => ['en' => 'LED Strip 24V, 14.4W/m, CRI 90, 5m', 'ar' => 'شريط ليد 24 فولت، 14.4 وات/م، 5م'], 'variants' => ['en' => '4 CCT', 'ar' => '4 درجات'], 'label' => ['en' => 'Cut-out: LED strip reel', 'ar' => 'Cut-out: LED strip reel']],
            ['id' => 'p9', 'cat' => 'smart', 'brand' => 'ABB', 'sku' => 'ABB-ZB-PIR-C', 'price' => 890, 'photo' => 17, 'smart' => true, 'weight' => 55, 'stock' => 'in', 'name' => ['en' => 'Zigbee Motion Sensor, ceiling', 'ar' => 'حساس حركة زيجبي للسقف'], 'variants' => ['en' => '360° · 7m range', 'ar' => '360° · مدى 7م'], 'label' => ['en' => 'Cut-out: ceiling sensor', 'ar' => 'Cut-out: ceiling sensor']],
            ['id' => 'p10', 'cat' => 'pend', 'brand' => 'Flos', 'sku' => 'FL-GLB-300-E27', 'price' => 7900, 'photo' => 33, 'smart' => false, 'weight' => 65, 'finishes' => ['#C9A45C', '#1E1E1C'], 'stock' => 'low', 'name' => ['en' => 'Opal Globe Pendant Ø300, E27', 'ar' => 'معلقة كرة أوبال Ø300، E27'], 'variants' => ['en' => '2 sizes · brass or black', 'ar' => 'مقاسين · نحاسي أو أسود'], 'label' => ['en' => 'Cut-out: opal glass globe', 'ar' => 'Cut-out: opal glass globe']],
            ['id' => 'p11', 'cat' => 'elec', 'brand' => 'Legrand', 'sku' => 'LG-ARB-2S-USBC', 'price' => 640, 'photo' => 12, 'weight' => 45, 'finishes' => ['#F4F3EF', '#1E1E1C', '#9FA3A8'], 'stock' => 'in', 'name' => ['en' => 'Double Socket 2×16A with USB-C', 'ar' => 'بريزة مزدوجة 2×16A مع USB-C'], 'variants' => ['en' => '3 finishes', 'ar' => '3 تشطيبات'], 'label' => ['en' => 'Cut-out: wall socket', 'ar' => 'Cut-out: wall socket']],
            ['id' => 'p12', 'cat' => 'down', 'brand' => 'Philips', 'sku' => 'PH-ADJ-7-27', 'price' => 520, 'photo' => 14, 'best' => true, 'ships' => true, 'weight' => 75, 'stock' => 'in', 'name' => ['en' => 'Adjustable Downlight 7W, anti-glare', 'ar' => 'سبوت متحرك 7 وات مضاد للانبهار'], 'variants' => ['en' => '2700K · 3000K', 'ar' => '2700K · 3000K'], 'label' => ['en' => 'Cut-out: gimbal downlight', 'ar' => 'Cut-out: gimbal downlight']],
        ];
    }
}
