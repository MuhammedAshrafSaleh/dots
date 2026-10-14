<?php

namespace Database\Seeders;

use App\Enums\PostStatus;
use App\Enums\ProjectStage;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use App\Models\Tag;
use App\Support\Slug;
use Database\Seeders\Concerns\SeedsDesignMedia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ContentSeeder extends Seeder
{
    use SeedsDesignMedia;

    public function run(): void
    {
        $this->seedServices();
        $this->seedProjects();
        $this->seedBlog();
        $this->seedPages();
    }

    private function seedServices(): void
    {
        $services = [
            ['design', '01', 19, ['en' => 'Lighting design', 'ar' => 'تصميم الإضاءة'], ['en' => 'Lux calculations, DIALux renders and a fixture schedule matched to your budget.', 'ar' => 'حسابات شدة الإضاءة وتصميمات DIALux وجدول وحدات يناسب ميزانيتك.'], ['en' => 'From concept to delivery in 3–6 weeks', 'ar' => 'من الفكرة للتسليم خلال 3–6 أسابيع']],
            ['smart', '02', 17, ['en' => 'Smart home installation', 'ar' => 'تركيب المنزل الذكي'], ['en' => 'Lighting scenes, curtains, climate and security on one app — installed and programmed.', 'ar' => 'مشاهد الإضاءة والستائر والتكييف والأمان في تطبيق واحد — تركيب وبرمجة.'], ['en' => 'Typical flat in 1–2 weeks', 'ar' => 'الشقة عادة خلال 1–2 أسبوع']],
            ['contract', '03', 18, ['en' => 'Electrical contracting', 'ar' => 'مقاولات كهربائية'], ['en' => 'Wiring, distribution boards and certified testing for villas, offices and retail.', 'ar' => 'تمديدات ولوحات توزيع واختبارات معتمدة للفيلات والمكاتب والمحلات.'], ['en' => 'Scheduled with your site programme', 'ar' => 'حسب برنامج الموقع']],
            ['maint', '04', 29, ['en' => 'Maintenance & support', 'ar' => 'الصيانة والدعم'], ['en' => 'Annual contracts, emergency call-outs and warranty handling for everything we supply.', 'ar' => 'عقود سنوية وزيارات طارئة ومتابعة الضمان لكل ما نوفره.'], ['en' => 'Same-week response', 'ar' => 'استجابة خلال نفس الأسبوع']],
        ];

        $included = [
            ['en' => 'A dedicated design engineer from brief to handover', 'ar' => 'مهندس تصميم مخصص من البداية للتسليم'],
            ['en' => 'Genuine products with official warranty', 'ar' => 'منتجات أصلية بضمان رسمي'],
            ['en' => 'One delivery and one clear invoice', 'ar' => 'توصيل واحد وفاتورة واحدة واضحة'],
            ['en' => 'Certified installation and testing', 'ar' => 'تركيب واختبار معتمد'],
        ];

        foreach ($services as $i => [$key, $num, $photo, $title, $desc, $duration]) {
            $service = Service::updateOrCreate(['slug->en' => Slug::make($title['en'], 'en')], [
                'number' => $num,
                'title' => $title,
                'slug' => Slug::fromTranslations($title),
                'excerpt' => $desc,
                'description' => $desc,
                'included_title' => ['en' => 'A complete, buildable package', 'ar' => 'باقة كاملة قابلة للتنفيذ'],
                'duration_text' => $duration,
                'show_in_footer' => true,
                'sort_order' => $i,
                'is_active' => true,
            ]);

            if (! $service->hasMedia('image')) {
                $this->attachPhoto($service, $photo, 'image');
            }

            $service->items()->delete();
            foreach ($included as $si => $text) {
                $service->items()->create(['text' => $text, 'sort_order' => $si]);
            }
        }

        // General services-page FAQ (K_FAQ) — faqable is null.
        $faqs = [
            [['en' => 'How much does a lighting design cost?', 'ar' => 'كم تكلفة تصميم الإضاءة؟'], ['en' => 'Residential designs start from EGP 6,000 and are fully credited against a supply order above EGP 60,000.', 'ar' => 'تبدأ التصميمات السكنية من 6,000 ج.م وتُخصم بالكامل عند طلب توريد يتجاوز 60,000 ج.م.']],
            [['en' => 'Which areas do you install in?', 'ar' => 'ما المناطق التي تركبون بها؟'], ['en' => 'Greater Cairo, Alexandria, the North Coast, Sokhna and Hurghada. Other governorates on request.', 'ar' => 'القاهرة الكبرى والإسكندرية والساحل الشمالي والسخنة والغردقة. باقي المحافظات حسب الطلب.']],
            [['en' => 'Can you work from my contractor’s drawings?', 'ar' => 'هل يمكنكم العمل على رسومات المقاول؟'], ['en' => 'Yes — send DWG or PDF plans and we return a lighting layout and priced schedule.', 'ar' => 'نعم — أرسل رسومات DWG أو PDF ونرسل لك توزيع الإضاءة وجدول أسعار.']],
            [['en' => 'What warranty do installations carry?', 'ar' => 'ما الضمان على التركيب؟'], ['en' => '12 months on workmanship, plus the manufacturer’s warranty on every product.', 'ar' => '12 شهر على التركيب، بالإضافة لضمان المصنع على كل منتج.']],
        ];
        Faq::whereNull('faqable_type')->delete();
        foreach ($faqs as $i => [$q, $a]) {
            Faq::create(['question' => $q, 'answer' => $a, 'sort_order' => $i]);
        }
    }

    private function seedProjects(): void
    {
        $stages = [ProjectStage::Design, ProjectStage::FirstFix, ProjectStage::Installation, ProjectStage::Commissioning, ProjectStage::Handover];

        $current = [
            ['pr1', ProjectType::Residential, 68, 2, 38, [38, 5, 27, 34, 0, 15], true, ['en' => 'Villa 27 · Palm Hills', 'ar' => 'فيلا 27 · بالم هيلز'], ['en' => '6th of October, Giza', 'ar' => '6 أكتوبر، الجيزة'], ['en' => 'Second fix & fixtures', 'ar' => 'التشطيب النهائي والوحدات'], ['en' => 'Lighting design, full electrical, KNX smart home', 'ar' => 'تصميم إضاءة، كهرباء كاملة، منزل ذكي KNX'], 'Mar 2026', 'Nov 2026'],
            ['pr2', ProjectType::Commercial, 42, 1, 11, [11, 10, 30, 9, 32], false, ['en' => 'Tech HQ · 4 floors', 'ar' => 'مقر شركة تقنية · 4 أدوار'], ['en' => 'New Administrative Capital', 'ar' => 'العاصمة الإدارية الجديدة'], ['en' => 'First fix & containment', 'ar' => 'التمديدات والمواسير'], ['en' => 'DALI office lighting, power, data containment', 'ar' => 'إضاءة مكاتب DALI، قوى، مسارات بيانات'], 'May 2026', 'Feb 2027'],
            ['pr3', ProjectType::Outdoor, 85, 3, 4, [4, 6, 39, 5], false, ['en' => 'Compound landscape & facades', 'ar' => 'لاندسكيب وواجهات كمباوند'], ['en' => 'New Cairo', 'ar' => 'القاهرة الجديدة'], ['en' => 'Commissioning', 'ar' => 'التشغيل والاختبار'], ['en' => 'Facade wash, bollards, street lighting, astro-timers', 'ar' => 'إضاءة واجهات وأعمدة قصيرة وإنارة شوارع ومؤقتات'], 'Jan 2026', 'Oct 2026'],
            ['pr4', ProjectType::SmartHome, 24, 0, 36, [36, 17, 28, 35], false, ['en' => 'Penthouse smart retrofit', 'ar' => 'تحويل بنتهاوس لمنزل ذكي'], ['en' => 'Zamalek, Cairo', 'ar' => 'الزمالك، القاهرة'], ['en' => 'Design approval', 'ar' => 'اعتماد التصميم'], ['en' => 'Wireless retrofit, scenes, curtains, climate', 'ar' => 'تحويل لاسلكي، مشاهد، ستائر، تكييف'], 'Aug 2026', 'Dec 2026'],
            ['pr5', ProjectType::Hospitality, 55, 2, 25, [25, 24, 35], false, ['en' => 'All-day café chain · 3 branches', 'ar' => 'سلسلة مقاهي · 3 فروع'], ['en' => 'Sheikh Zayed & Maadi', 'ar' => 'الشيخ زايد والمعادي'], ['en' => 'Fixture installation', 'ar' => 'تركيب الوحدات'], ['en' => 'Decorative & track lighting, controls', 'ar' => 'إضاءة ديكورية وتراك وتحكم'], 'Jun 2026', 'Oct 2026'],
            ['pr6', ProjectType::Residential, 12, 0, 3, [3, 16, 26], false, ['en' => 'Twin houses · Hacienda', 'ar' => 'توين هاوس · هاسيندا'], ['en' => 'North Coast', 'ar' => 'الساحل الشمالي'], ['en' => 'Site survey', 'ar' => 'المعاينة'], ['en' => 'Supply & install, outdoor, pool lighting', 'ar' => 'توريد وتركيب، إضاءة خارجية وحمامات سباحة'], 'Sep 2026', 'Apr 2027'],
        ];

        foreach ($current as $i => [$key, $type, $progress, $stepIdx, $cover, $gallery, $featured, $title, $location, $phase, $scope, $start, $eta]) {
            $project = Project::updateOrCreate(['slug->en' => Slug::make($title['en'], 'en')], [
                'status' => ProjectStatus::Current, 'type' => $type, 'title' => $title, 'slug' => Slug::fromTranslations($title),
                'location' => $location, 'progress' => $progress, 'stage' => $stages[$stepIdx], 'phase_label' => $phase,
                'scope_summary' => $scope, 'about' => ['en' => '<p>'.$title['en'].' — a live DOTS project.</p>', 'ar' => '<p>'.$title['ar'].' — مشروع جارٍ لدى دوتس.</p>'],
                'start_date' => Carbon::parse($start)->startOfMonth(), 'expected_completion' => Carbon::parse($eta)->startOfMonth(),
                'is_featured' => $featured, 'sort_order' => $i, 'is_active' => true,
            ]);
            $this->attachGallery($project, $cover, $gallery);
        }

        $done = [
            ['d1', ProjectType::Hospitality, 2026, 23, [23, 22, 6, 5], ['en' => 'Red Sea resort relight', 'ar' => 'إعادة إضاءة منتجع البحر الأحمر'], ['en' => 'El Gouna', 'ar' => 'الجونة'], ['en' => 'Private resort group', 'ar' => 'مجموعة منتجعات خاصة'], ['en' => '1,400 fixtures · −38% energy', 'ar' => '1,400 وحدة · توفير 38%']],
            ['d2', ProjectType::Commercial, 2025, 24, [24, 25, 35], ['en' => 'Restaurant, Zamalek', 'ar' => 'مطعم بالزمالك'], ['en' => 'Zamalek, Cairo', 'ar' => 'الزمالك، القاهرة'], null, ['en' => 'Fit-out in 19 days', 'ar' => 'تنفيذ في 19 يوماً']],
            ['d3', ProjectType::Residential, 2025, 0, [0, 13, 15, 34], ['en' => 'Apartment, Mivida', 'ar' => 'شقة في ميفيدا'], ['en' => 'New Cairo', 'ar' => 'القاهرة الجديدة'], null, ['en' => 'Lighting design + supply', 'ar' => 'تصميم + توريد']],
            ['d4', ProjectType::Commercial, 2024, 9, [9, 11, 30], ['en' => 'Co-working campus', 'ar' => 'مساحة عمل مشتركة'], ['en' => 'Smart Village', 'ar' => 'القرية الذكية'], ['en' => 'Public listing', 'ar' => 'متاح للنشر'], ['en' => 'DALI + daylight sensors', 'ar' => 'DALI + حساسات ضوء نهار']],
            ['d5', ProjectType::SmartHome, 2026, 2, [2, 17, 27], ['en' => 'Smart villa, Allegria', 'ar' => 'فيلا ذكية، أليجريا'], ['en' => 'Sheikh Zayed', 'ar' => 'الشيخ زايد'], null, ['en' => 'KNX, 64 scenes', 'ar' => 'KNX، 64 مشهد']],
            ['d6', ProjectType::Outdoor, 2024, 5, [5, 6, 38], ['en' => 'Villa pool & garden', 'ar' => 'حمام سباحة وحديقة فيلا'], ['en' => 'Marassi, North Coast', 'ar' => 'مراسي، الساحل الشمالي'], null, ['en' => 'Underwater & landscape', 'ar' => 'إضاءة تحت الماء ولاندسكيب']],
            ['d7', ProjectType::Residential, 2023, 14, [14, 13, 1], ['en' => 'Family kitchen, Maadi', 'ar' => 'مطبخ عائلي، المعادي'], ['en' => 'Maadi, Cairo', 'ar' => 'المعادي، القاهرة'], null, ['en' => 'Task & pendant lighting', 'ar' => 'إضاءة وظيفية ومعلقات']],
            ['d8', ProjectType::Commercial, 2023, 32, [32, 31, 10], ['en' => 'Office tower lobby', 'ar' => 'مدخل برج إداري'], ['en' => 'Downtown Cairo', 'ar' => 'وسط القاهرة'], null, ['en' => 'Architectural + emergency', 'ar' => 'معمارية + طوارئ']],
        ];

        foreach ($done as $i => [$key, $type, $year, $cover, $gallery, $title, $location, $client, $scope]) {
            $project = Project::updateOrCreate(['slug->en' => Slug::make($title['en'], 'en')], [
                'status' => ProjectStatus::Completed, 'type' => $type, 'title' => $title, 'slug' => Slug::fromTranslations($title),
                'location' => $location, 'client' => $client, 'completed_year' => $year, 'progress' => 100,
                'scope_summary' => $scope,
                'about' => ['en' => '<p>'.$title['en'].' — completed by DOTS.</p>', 'ar' => '<p>'.$title['ar'].' — من تنفيذ دوتس.</p>'],
                'challenge' => ['en' => 'A tight programme with a demanding brief.', 'ar' => 'برنامج زمني ضيق ومتطلبات دقيقة.'],
                'solution' => ['en' => 'A phased plan and a single accountable team.', 'ar' => 'خطة على مراحل وفريق واحد مسؤول.'],
                'result' => ['en' => 'Delivered on time, on budget.', 'ar' => 'تم التسليم في الوقت والميزانية.'],
                'is_featured' => false, 'sort_order' => $i, 'is_active' => true,
            ]);
            $this->attachGallery($project, $cover, $gallery);
        }
    }

    /** @param  array<int, int>  $gallery */
    private function attachGallery(Project $project, int $cover, array $gallery): void
    {
        if (! $project->hasMedia('cover')) {
            $this->attachPhoto($project, $cover, 'cover');
        }
        if (! $project->hasMedia('gallery')) {
            foreach ($gallery as $photo) {
                $this->attachPhoto($project, $photo, 'gallery');
            }
        }
    }

    private function seedBlog(): void
    {
        $cats = [
            'guides' => ['en' => 'Lighting guides', 'ar' => 'أدلة الإضاءة'],
            'smart' => ['en' => 'Smart home', 'ar' => 'المنزل الذكي'],
            'projects' => ['en' => 'Project stories', 'ar' => 'قصص المشروعات'],
            'tech' => ['en' => 'Technical', 'ar' => 'تقني'],
            'news' => ['en' => 'News', 'ar' => 'أخبار'],
        ];
        $categories = [];
        $i = 0;
        foreach ($cats as $key => $name) {
            $categories[$key] = PostCategory::updateOrCreate(['slug->en' => Slug::make($name['en'], 'en')],
                ['name' => $name, 'slug' => Slug::fromTranslations($name), 'sort_order' => $i++]);
        }

        $tagNames = [
            'ambient' => ['en' => 'Ambient', 'ar' => 'إضاءة عامة'],
            'knx' => ['en' => 'KNX', 'ar' => 'KNX'],
            'energy' => ['en' => 'Energy saving', 'ar' => 'توفير الطاقة'],
        ];
        $tags = [];
        foreach ($tagNames as $key => $name) {
            $tags[$key] = Tag::updateOrCreate(['slug' => $key], ['name' => $name]);
        }

        $articles = [
            ['a1', 'guides', 33, 8, '12 Sep 2026', true, ['en' => 'Nour Hassan', 'ar' => 'نور حسن'], ['en' => 'Lighting designer', 'ar' => 'مصممة إضاءة'], ['en' => 'Layered lighting: how to light a living room in three layers', 'ar' => 'الإضاءة المتعددة الطبقات: كيف تضيء غرفة المعيشة بثلاث طبقات'], ['en' => 'Ambient, task and accent — the simple framework our designers use on every home.', 'ar' => 'عامة ووظيفية وجمالية — الإطار البسيط الذي يستخدمه مصممونا في كل منزل.'], ['ambient']],
            ['a2', 'smart', 17, 6, '3 Sep 2026', false, null, null, ['en' => 'Zigbee, Wi-Fi or KNX? Choosing a smart home backbone', 'ar' => 'زيجبي أم واي فاي أم KNX؟ اختيار أساس المنزل الذكي'], ['en' => 'What each protocol is good at, and when to wire instead of going wireless.', 'ar' => 'مميزات كل بروتوكول، ومتى تختار التوصيل السلكي.'], ['knx']],
            ['a3', 'projects', 23, 5, '27 Aug 2026', false, null, null, ['en' => 'Relighting a Red Sea resort without closing a single room', 'ar' => 'إعادة إضاءة منتجع على البحر الأحمر دون إغلاق غرفة واحدة'], ['en' => 'Phased night works, 1,400 fixtures and a 38% energy saving in El Gouna.', 'ar' => 'أعمال ليلية على مراحل و1,400 وحدة وتوفير 38% في الجونة.'], ['energy']],
            ['a4', 'tech', 8, 7, '19 Aug 2026', false, null, null, ['en' => 'Reading a datasheet: lumens, CRI, UGR and beam angle explained', 'ar' => 'قراءة الداتا شيت: شرح اللومن وCRI وUGR وزاوية الإضاءة'], ['en' => 'The six numbers that decide whether a fixture will look right in your space.', 'ar' => 'الأرقام الستة التي تحدد إن كانت الوحدة مناسبة لمساحتك.'], []],
            ['a5', 'guides', 14, 4, '8 Aug 2026', false, null, null, ['en' => 'Kitchen lighting that actually works for cooking', 'ar' => 'إضاءة مطبخ مناسبة فعلاً للطبخ'], ['en' => 'Under-cabinet strips, island pendants and the right colour temperature.', 'ar' => 'شرائط تحت الدواليب ومعلقات الجزيرة ودرجة اللون المناسبة.'], ['ambient']],
            ['a6', 'news', 12, 3, '1 Aug 2026', false, null, null, ['en' => 'Our new Sheikh Zayed showroom is open', 'ar' => 'افتتاح معرضنا الجديد في الشيخ زايد'], ['en' => '800 m² of live lighting scenes and a smart home demo flat.', 'ar' => '800 م² من مشاهد الإضاءة الحية وشقة عرض للمنزل الذكي.'], []],
            ['a7', 'tech', 29, 6, '22 Jul 2026', false, null, null, ['en' => 'Sizing a distribution board for a modern villa', 'ar' => 'تحديد حجم لوحة التوزيع لفيلا حديثة'], ['en' => 'Circuits, RCD protection and spare ways — a practical checklist.', 'ar' => 'الدوائر وحماية التسريب والخانات الاحتياطية — قائمة عملية.'], []],
            ['a8', 'smart', 2, 5, '10 Jul 2026', false, null, null, ['en' => 'Five lighting scenes every smart home should have', 'ar' => 'خمسة مشاهد إضاءة يحتاجها كل منزل ذكي'], ['en' => 'Morning, focus, dinner, movie and night path — with exact dim levels.', 'ar' => 'الصباح والتركيز والعشاء والأفلام والممر الليلي — بمستويات التعتيم.'], ['knx']],
            ['a9', 'projects', 24, 4, '2 Jul 2026', false, null, null, ['en' => 'Warm light, fast turns: a Zamalek restaurant fit-out', 'ar' => 'إضاءة دافئة وتنفيذ سريع: تجهيز مطعم بالزمالك'], ['en' => 'Designing for mood at night and cleanability by day, in 19 days.', 'ar' => 'تصميم للأجواء ليلاً وسهولة التنظيف نهاراً، في 19 يوماً.'], ['energy']],
        ];

        $productIds = Product::query()->orderBy('id')->take(3)->pluck('id')->all();

        foreach ($articles as $i => [$key, $cat, $photo, $read, $date, $featured, $authorName, $authorRole, $title, $excerpt, $tagKeys]) {
            $post = Post::updateOrCreate(['slug->en' => Slug::make($title['en'], 'en')], [
                'post_category_id' => $categories[$cat]->id,
                'title' => $title, 'slug' => Slug::fromTranslations($title), 'excerpt' => $excerpt,
                'body' => ['en' => '<p>'.$excerpt['en'].'</p>', 'ar' => '<p>'.$excerpt['ar'].'</p>'],
                'author_name' => $authorName ?? ['en' => 'DOTS editorial team', 'ar' => 'فريق تحرير دوتس'],
                'author_role' => $authorRole,
                'read_minutes' => $read, 'is_featured' => $featured,
                'status' => PostStatus::Published, 'published_at' => Carbon::parse($date),
            ]);

            if (! $post->hasMedia('cover')) {
                $this->attachPhoto($post, $photo, 'cover');
            }
            $post->tags()->sync(array_map(fn (string $k) => $tags[$k]->id, $tagKeys));
            if ($productIds !== [] && $i < 2) {
                $post->products()->syncWithoutDetaching([$productIds[0] => ['sort_order' => 0]]);
            }
        }
    }

    private function seedPages(): void
    {
        $pages = $this->pageData();

        $sort = 0;
        foreach ($pages as $key => [$title, $lede, $blocks, $footer]) {
            Page::updateOrCreate(['key' => $key], [
                'title' => $title,
                'slug' => Slug::fromTranslations($title),
                'lede' => $lede,
                'body' => ['en' => $this->blocksToHtml($blocks, 'en'), 'ar' => $this->blocksToHtml($blocks, 'ar')],
                'show_in_footer' => $footer,
                'sort_order' => $sort++,
                'is_active' => true,
            ]);
        }
    }

    /** @param  array<int, array{0: string, 1: mixed}>  $blocks */
    private function blocksToHtml(array $blocks, string $locale): string
    {
        $html = '';
        foreach ($blocks as [$type, $payload]) {
            if ($type === 'ul' || $type === 'ol') {
                $items = is_array($payload) ? $payload : [];
                $lis = '';
                foreach ($items as $li) {
                    $lis .= '<li>'.(is_array($li) ? ($li[$locale] ?? '') : '').'</li>';
                }
                $html .= "<{$type}>{$lis}</{$type}>";

                continue;
            }

            $text = is_array($payload) ? ($payload[$locale] ?? '') : '';
            $html .= match ($type) {
                'h2' => "<h2>{$text}</h2>",
                'h3' => "<h3>{$text}</h3>",
                'p' => "<p>{$text}</p>",
                'callout' => "<div class=\"d-callout\">{$text}</div>",
                default => '',
            };
        }

        return $html;
    }

    /**
     * @return array<string, array{0: array<string, string>, 1: array<string, string>, 2: array<int, array{0: string, 1: mixed}>, 3: bool}>
     */
    private function pageData(): array
    {
        $m = fn (string $en, string $ar) => ['en' => $en, 'ar' => $ar];

        return [
            'delivery' => [$m('Delivery', 'التوصيل'), $m('Where we deliver, how long it takes and what it costs.', 'أين نوصّل، وكم يستغرق، وكم يكلف.'), [
                ['h2', $m('Delivery times', 'مواعيد التوصيل')],
                ['p', $m('Orders confirmed before 15:00 on a working day leave our Nasr City warehouse the same day. The courier calls you one hour before arriving.', 'الطلبات المؤكدة قبل الساعة 3 عصراً في يوم عمل تخرج من مخزن مدينة نصر في نفس اليوم. المندوب يتصل بك قبل الوصول بساعة.')],
                ['ul', [$m('Cairo & Giza — next working day', 'القاهرة والجيزة — يوم العمل التالي'), $m('Alexandria, Delta & Canal cities — 2–3 working days', 'الإسكندرية والدلتا ومدن القناة — 2–3 أيام عمل'), $m('Upper Egypt, Red Sea & Sinai — 3–5 working days', 'الصعيد والبحر الأحمر وسيناء — 3–5 أيام عمل')]],
                ['h2', $m('Delivery fees', 'رسوم التوصيل')],
                ['p', $m('Standard delivery is EGP 150 and free on orders over EGP 5,000.', 'التوصيل العادي بـ 150 ج.م ومجاني للطلبات فوق 5,000 ج.م.')],
                ['callout', $m('Project orders are scheduled with our project team — send your BOQ on WhatsApp.', 'طلبات المشروعات تُجدول مع فريق المشروعات — أرسل المقايسة على واتساب.')],
            ], true],
            'returns' => [$m('Returns', 'الاسترجاع'), $m('14 days to change your mind on unused items in their original packaging.', '14 يوماً لتغيير رأيك في المنتجات غير المستخدمة بتغليفها الأصلي.'), [
                ['h2', $m('What you can return', 'ما يمكن استرجاعه')],
                ['p', $m('Most items can be returned within 14 days of delivery if unused and in the original box.', 'يمكن استرجاع معظم المنتجات خلال 14 يوماً إذا كانت غير مستخدمة وفي علبتها الأصلية.')],
                ['callout', $m('Refunds go back to the original payment method within 5–7 working days.', 'يُرد المبلغ لنفس طريقة الدفع خلال 5–7 أيام عمل.')],
            ], true],
            'warranty' => [$m('Warranty', 'الضمان'), $m('Official manufacturer warranty on everything we sell — handled by us.', 'ضمان رسمي من الشركة المصنعة على كل ما نبيعه — نتابعه نحن.'), [
                ['h2', $m('Coverage', 'التغطية')],
                ['ul', [$m('LED fittings and drivers — 2 to 5 years by brand', 'وحدات وأجهزة تشغيل الليد — من 2 إلى 5 سنوات حسب العلامة'), $m('Smart home devices — 2 years', 'أجهزة المنزل الذكي — سنتان'), $m('Dots installation workmanship — 1 year', 'أعمال التركيب من دوتس — سنة واحدة')]],
                ['callout', $m('Keep your order number — it is your warranty card.', 'احتفظ برقم الطلب — فهو بطاقة الضمان.')],
            ], true],
            'careers' => [$m('Careers', 'الوظائف'), $m('Engineers, installers and client people who like getting the details right.', 'مهندسون وفنيون وفريق خدمة عملاء يحبون الدقة في التفاصيل.'), [
                ['h2', $m('Working at Dots', 'العمل في دوتس')],
                ['p', $m('We design, supply and install lighting, smart home and electrical systems across Egypt.', 'نصمم ونورد ونركب أنظمة الإضاءة والمنزل الذكي والكهرباء في كل مصر.')],
                ['callout', $m('Send your CV and the role title on WhatsApp or to careers@dots.eg.', 'أرسل سيرتك الذاتية واسم الوظيفة على واتساب أو إلى careers@dots.eg.')],
            ], true],
            'terms' => [$m('Terms & conditions', 'الشروط والأحكام'), $m('The terms that govern buying from DOTS.', 'الشروط التي تحكم الشراء من دوتس.'), [
                ['p', $m('These terms will be finalised before launch. Placeholder content.', 'ستُستكمل هذه الشروط قبل الإطلاق. محتوى مؤقت.')],
            ], true],
            'privacy' => [$m('Privacy policy', 'سياسة الخصوصية'), $m('How we handle your data.', 'كيف نتعامل مع بياناتك.'), [
                ['p', $m('This privacy policy will be finalised before launch. Placeholder content.', 'ستُستكمل سياسة الخصوصية قبل الإطلاق. محتوى مؤقت.')],
            ], true],
        ];
    }
}
