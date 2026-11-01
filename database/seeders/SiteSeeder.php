<?php

namespace Database\Seeders;

use App\Models\PageSection;
use App\Services\Settings;
use Database\Seeders\Concerns\SeedsDesignMedia;
use Illuminate\Database\Seeder;

/**
 * page_sections (per config/page_sections.php) and settings defaults. All
 * marketing copy is verbatim from the design JSX (Home/About/Services/Brands/
 * Projects/Blog.jsx, Shared.jsx KQuoteBand, Footer.jsx, Navbar.jsx,
 * OrderSummary.jsx, MegaMenu.jsx). Keys the design has no copy for are marked
 * "no design copy" and listed in the Phase 2 report.
 */
class SiteSeeder extends Seeder
{
    use SeedsDesignMedia;

    /** @return array<string, string> */
    private function m(string $en, string $ar): array
    {
        return ['en' => $en, 'ar' => $ar];
    }

    /** @param  array<string, mixed>  $content */
    private function section(string $page, string $key, array $content): PageSection
    {
        return PageSection::updateOrCreate(
            ['page' => $page, 'key' => $key],
            ['content' => $content, 'is_active' => true],
        );
    }

    public function run(): void
    {
        $this->seedSections();
        $this->seedSettings();
    }

    private function seedSections(): void
    {
        // ---- Home (Home.jsx) ----
        $hero = $this->section('home', 'hero', [
            'eyebrow' => $this->m('Lighting · Smart Home · Electrical', 'إضاءة · منزل ذكي · كهرباء'),
            'headline' => $this->m('Light, done right — from first sketch to final switch.', 'إضاءة بإتقان — من أول فكرة لآخر مفتاح.'),
            'subheadline' => $this->m('10,000+ products from 60+ brands, with lighting design, installation and after-sales support across Egypt.', 'أكثر من 10,000 منتج من 60+ علامة تجارية، مع تصميم الإضاءة والتركيب وخدمة ما بعد البيع في كل مصر.'),
            'button1_label' => $this->m('Shop lighting', 'تسوق الإضاءة'),
            'button1_link' => '/products',
            'button2_label' => $this->m('Book lighting design', 'احجز تصميم إضاءة'),
            'button2_link' => '/services',
        ]);
        if (! $hero->hasMedia('section')) {
            $this->attachFile($hero, 'heroVid.mp4', 'section');
            $this->attachPhoto($hero, 0, 'section', ['field' => 'poster']);
        }

        $this->section('home', 'features', ['items' => [
            ['icon' => 'truck', 'text' => $this->m('Delivery to all governorates', 'توصيل لكل المحافظات')],
            ['icon' => 'wrench', 'text' => $this->m('Certified installation', 'تركيب معتمد')],
            ['icon' => 'shield-check', 'text' => $this->m('Official brand warranty', 'ضمان رسمي')],
            ['icon' => 'message-circle', 'text' => $this->m('Engineers on WhatsApp', 'مهندسين على واتساب')],
        ]]);

        $this->section('home', 'categories_head', [
            'eyebrow' => $this->m('Shop by category', 'تسوق حسب القسم'),
            'title' => $this->m('Everything that lights, switches and connects', 'كل ما يضيء ويتحكم ويوصل'),
            'button_label' => $this->m('All categories', 'كل الأقسام'),
        ]);

        $this->section('home', 'featured_head', [
            'eyebrow' => $this->m('Featured', 'مختارات'),
            'title' => $this->m('In the showroom this month', 'في المعرض هذا الشهر'),
            'tab_new_label' => $this->m('New arrivals', 'وصل حديثاً'),
            'tab_best_label' => $this->m('Best sellers', 'الأكثر مبيعاً'),
            'tab_smart_label' => $this->m('Smart home', 'منزل ذكي'),
        ]);

        $this->section('home', 'services_head', [
            'eyebrow' => $this->m('Services', 'الخدمات'),
            'title' => $this->m('We don’t just supply it. We design, install and maintain it.', 'لا نكتفي بالتوريد. نصمم ونركب ونصون.'),
            'button_label' => $this->m('All services', 'كل الخدمات'),
        ]);

        $smart = $this->section('home', 'smart_banner', [
            'eyebrow' => $this->m('Smart home', 'المنزل الذكي'),
            'title' => $this->m('Your whole home, one tap away.', 'بيتك كله بلمسة واحدة.'),
            'text' => $this->m('Scenes for lighting, curtains and AC — installed, programmed and supported by Dots engineers.', 'مشاهد للإضاءة والستائر والتكييف — تركيب وبرمجة ودعم من مهندسي دوتس.'),
            'button1_label' => $this->m('Book a home demo', 'احجز عرض منزلي'),
            'button1_link' => '/services',
            'button2_label' => $this->m('Shop smart devices', 'تسوق الأجهزة الذكية'),
            'button2_link' => '/products',
        ]);
        if (! $smart->hasMedia('section')) {
            $this->attachPhoto($smart, 2, 'section', ['field' => 'image']);
        }

        $this->section('home', 'brands_head', [
            'eyebrow' => $this->m('Brands', 'العلامات التجارية'),
            'title' => $this->m('Authorised partner for 60+ manufacturers', 'شريك معتمد لأكثر من 60 مصنع'),
            'button_label' => $this->m('All brands', 'كل العلامات'),
        ]);

        // ---- About (About.jsx) ----
        $aboutHero = $this->section('about', 'hero', [
            'eyebrow' => $this->m('About Dots', 'عن دوتس'),
            'title' => $this->m('We make spaces work better in light.', 'نجعل المساحات أجمل وأذكى بالضوء.'),
            'text' => $this->m('Since 2008, Dots has supplied, designed and installed lighting, smart home and electrical systems for homes and landmark projects across Egypt.', 'منذ 2008 توفر دوتس وتصمم وتركب أنظمة الإضاءة والمنزل الذكي والكهرباء للمنازل والمشروعات الكبرى في مصر.'),
            'button1_label' => $this->m('See our work', 'شاهد أعمالنا'),
            'button1_link' => '/projects',
            'button2_label' => $this->m('Watch our story', 'شاهد قصتنا'),
            'button2_link' => '', // design opens a video, no link
        ]);
        if (! $aboutHero->hasMedia('section')) {
            $this->attachPhoto($aboutHero, 4, 'section', ['field' => 'image']);
        }

        $story = $this->section('about', 'story', [
            'eyebrow' => $this->m('Our story', 'قصتنا'),
            'title' => $this->m('From a Heliopolis lighting counter to a full-service engineering house.', 'من محل إضاءة في مصر الجديدة إلى بيت هندسي متكامل الخدمات.'),
            'text' => $this->m('We started by selling quality fixtures to architects who were tired of guesswork. Customers kept asking us to install them, then to design the whole scheme, then to automate it. Today one Dots team takes a project from first sketch to final switch — and stays for the years after.', 'بدأنا ببيع وحدات إضاءة عالية الجودة لمعماريين سئموا التخمين. طلب العملاء منا التركيب، ثم تصميم المنظومة كاملة، ثم أتمتتها. اليوم يأخذ فريق دوتس واحد المشروع من أول فكرة لآخر مفتاح — ويبقى معك في السنوات التالية.'),
        ]);
        if (! $story->hasMedia('section')) {
            foreach ([12, 19, 18] as $n) {
                $this->attachPhoto($story, $n, 'section', ['field' => 'images']);
            }
        }

        $this->section('about', 'stats', ['items' => [
            ['value' => '18', 'suffix' => '+', 'label' => $this->m('Years of experience', 'سنة خبرة')],
            ['value' => '2400', 'suffix' => '+', 'label' => $this->m('Projects delivered', 'مشروع منجز')],
            ['value' => '60', 'suffix' => '+', 'label' => $this->m('Brands carried', 'علامة تجارية')],
            ['value' => '10000', 'suffix' => '+', 'label' => $this->m('Products in catalogue', 'منتج في الكتالوج')],
        ]]);

        $mv = $this->section('about', 'mission_vision', [
            'mission_text' => $this->m('To make well-designed light and reliable electrical systems simple to buy, install and live with.', 'أن نجعل الإضاءة المصممة جيداً والأنظمة الكهربائية الموثوقة سهلة الشراء والتركيب والاستخدام.'),
            'vision_text' => $this->m('To be the first call for every home, architect and developer in Egypt who cares how a space feels after dark.', 'أن نكون الاختيار الأول لكل منزل ومعماري ومطور في مصر يهتم بشعور المساحة بعد الغروب.'),
        ]);
        if (! $mv->hasMedia('section')) {
            $this->attachPhoto($mv, 26, 'section', ['field' => 'mission_image']);
            $this->attachPhoto($mv, 38, 'section', ['field' => 'vision_image']);
        }

        $disciplines = $this->section('about', 'disciplines', [
            'eyebrow' => $this->m('What we do', 'ماذا نقدم'),
            'title' => $this->m('Three disciplines, one accountable team', 'ثلاثة تخصصات وفريق واحد مسؤول'),
            'items' => [
                ['title' => $this->m('Lighting', 'الإضاءة'), 'text' => $this->m('Decorative, architectural and outdoor lighting from 60+ brands — specified, supplied and aimed on site.', 'إضاءة ديكورية ومعمارية وخارجية من أكثر من 60 علامة — تصميم وتوريد وضبط في الموقع.'), 'icon' => 'lightbulb', 'link' => '/products'],
                ['title' => $this->m('Smart Home', 'المنزل الذكي'), 'text' => $this->m('Scenes, curtains, climate and security on one app, designed around how your family lives.', 'مشاهد وستائر وتكييف وأمان في تطبيق واحد، مصممة حول أسلوب حياة أسرتك.'), 'icon' => 'wifi', 'link' => '/services'],
                ['title' => $this->m('Electrical', 'الكهرباء'), 'text' => $this->m('Wiring devices, distribution and certified contracting for homes, offices and hospitality.', 'مفاتيح وبرايز ولوحات توزيع ومقاولات معتمدة للمنازل والمكاتب والفنادق.'), 'icon' => 'plug-zap', 'link' => '/services'],
            ],
        ]);
        if (! $disciplines->hasMedia('section')) {
            foreach ([33, 17, 29] as $idx => $n) {
                $this->attachPhoto($disciplines, $n, 'section', ['field' => 'item_image_'.$idx]);
            }
        }

        $this->section('about', 'why', [
            'eyebrow' => $this->m('Why Dots', 'لماذا دوتس'),
            'title' => $this->m('Why clients choose us — and come back', 'لماذا يختارنا العملاء — ويعودون'),
            'items' => [
                ['icon' => 'shield-check', 'title' => $this->m('Genuine, warrantied', 'أصلي ومضمون'), 'text' => $this->m('Authorised partner — every product with official Egyptian warranty.', 'شريك معتمد — كل منتج بضمان رسمي في مصر.')],
                ['icon' => 'pencil-ruler', 'title' => $this->m('Designed, not guessed', 'تصميم لا تخمين'), 'text' => $this->m('In-house lighting designers with DIALux calculations for every project.', 'مصممو إضاءة داخليون بحسابات DIALux لكل مشروع.')],
                ['icon' => 'hard-hat', 'title' => $this->m('Certified installers', 'فنيون معتمدون'), 'text' => $this->m('Our own technicians — no subcontracting, 12-month workmanship warranty.', 'فنيونا الخاصون — بدون مقاولين من الباطن، وضمان 12 شهر على التركيب.')],
                ['icon' => 'package', 'title' => $this->m('Stock that ships', 'مخزون جاهز'), 'text' => $this->m('10,000+ SKUs, 4,000 m² warehouse, next-day in Greater Cairo.', 'أكثر من 10,000 منتج ومخزن 4,000 م² وتوصيل اليوم التالي بالقاهرة الكبرى.')],
                ['icon' => 'file-text', 'title' => $this->m('Technical depth', 'عمق تقني'), 'text' => $this->m('Datasheets, IES files and BOQ pricing within 48 hours for consultants.', 'داتا شيت وملفات IES وتسعير مقايسات خلال 48 ساعة للاستشاريين.')],
                ['icon' => 'headset', 'title' => $this->m('Support after handover', 'دعم بعد التسليم'), 'text' => $this->m('Maintenance contracts and a WhatsApp line answered by engineers.', 'عقود صيانة وخط واتساب يرد عليه مهندسون.')],
            ],
        ]);

        $this->section('about', 'partners_head', [
            'eyebrow' => $this->m('Partners', 'شركاؤنا'),
            'title' => $this->m('Brands and manufacturers we represent', 'العلامات والمصانع التي نمثلها'),
            'button_label' => $this->m('All 60+ brands', 'كل العلامات'),
        ]);

        $cta = $this->section('about', 'cta', [
            'title' => $this->m('Planning a project? Let’s price it together.', 'تخطط لمشروع؟ دعنا نسعّره معاً.'),
            'text' => $this->m('Send drawings or a BOQ — an engineer replies within 2 working hours.', 'أرسل الرسومات أو المقايسة — ويرد مهندس خلال ساعتين عمل.'),
        ]);
        if (! $cta->hasMedia('section')) {
            $this->attachPhoto($cta, 37, 'section', ['field' => 'background_image']);
        }

        // ---- Services (Services.jsx) ----
        $this->section('services', 'head', [
            'eyebrow' => $this->m('Services', 'الخدمات'),
            'title' => $this->m('Design, install, maintain.', 'تصميم، تركيب، صيانة.'),
            'intro' => $this->m('One team from the first lux calculation to the last switch plate — for homes, offices, hotels and retail.', 'فريق واحد من أول حساب إضاءة لآخر مفتاح — للمنازل والمكاتب والفنادق والمحلات.'),
            'button_label' => $this->m('Talk to an engineer', 'كلم مهندس'),
        ]);

        $this->section('services', 'process', [
            'eyebrow' => $this->m('How it works', 'كيف نعمل'),
            'title' => $this->m('Four steps, one accountable team', 'أربع خطوات وفريق واحد مسؤول'),
            'items' => [
                ['title' => $this->m('Consult', 'استشارة'), 'text' => $this->m('Site visit or video call to understand the space and budget.', 'زيارة أو مكالمة فيديو لفهم المساحة والميزانية.')],
                ['title' => $this->m('Design', 'تصميم'), 'text' => $this->m('Layouts, renders and a fixture schedule you approve.', 'توزيع وتصميمات وجدول وحدات تعتمده.')],
                ['title' => $this->m('Supply', 'توريد'), 'text' => $this->m('Genuine products, one delivery, one invoice.', 'منتجات أصلية، توصيل واحد، فاتورة واحدة.')],
                ['title' => $this->m('Install', 'تركيب'), 'text' => $this->m('Certified technicians, testing and handover.', 'فنيين معتمدين واختبار وتسليم.')],
            ],
        ]);

        $this->section('services', 'faq_head', [
            'eyebrow' => $this->m('FAQ', 'أسئلة شائعة'),
            'title' => $this->m('Questions we hear every week', 'أسئلة نسمعها كل أسبوع'),
        ]);

        // ---- Brands (Brands.jsx) ----
        $this->section('brands', 'head', [
            'eyebrow' => $this->m('Authorised partner', 'شريك معتمد'),
            'title' => $this->m('Brands', 'العلامات التجارية'),
            'lede' => $this->m('Genuine products with official Egyptian warranty from 60+ manufacturers.', 'منتجات أصلية بضمان رسمي في مصر من أكثر من 60 مصنع.'),
        ]);

        // ---- Projects (Projects.jsx) ----
        $currentHero = $this->section('projects', 'current_hero', [
            'eyebrow' => $this->m('Current projects', 'مشروعات جارية'),
            'title' => $this->m('On site right now', 'في المواقع الآن'),
            'lede' => $this->m('Live progress from the homes, offices and landscapes our teams are delivering this season.', 'تقدم مباشر من المنازل والمكاتب والمساحات الخارجية التي تنفذها فرقنا هذا الموسم.'),
            'button_label' => $this->m('Start your project', 'ابدأ مشروعك'),
        ]);
        if (! $currentHero->hasMedia('section')) {
            $this->attachPhoto($currentHero, 20, 'section', ['field' => 'image']);
        }

        $previousHead = $this->section('projects', 'previous_head', [
            'eyebrow' => $this->m('Portfolio', 'أعمالنا'),
            'title' => $this->m('Previous projects', 'مشروعات سابقة'),
            'lede' => $this->m('2,400+ completed homes, offices, hotels and landscapes — a selection of case studies.', 'أكثر من 2,400 منزل ومكتب وفندق ومساحة خارجية منجزة — مختارات من دراسات الحالة.'),
        ]);
        if (! $previousHead->hasMedia('section')) {
            $this->attachPhoto($previousHead, 23, 'section', ['field' => 'image']);
        }

        // ---- Blog (Blog.jsx) ----
        $this->section('blog', 'head', [
            'eyebrow' => $this->m('Journal', 'المدونة'),
            'title' => $this->m('Ideas, guides & project stories', 'أفكار وأدلة وقصص مشروعات'),
            'lede' => $this->m('Practical lighting, smart home and electrical know-how from the Dots team.', 'معرفة عملية في الإضاءة والمنزل الذكي والكهرباء من فريق دوتس.'),
        ]);

        // ---- Global (Shared.jsx KQuoteBand, Footer.jsx, MegaMenu.jsx) ----
        $this->section('global', 'quote_band', [
            'eyebrow' => $this->m('Projects & trade', 'المشروعات والمقاولين'),
            'title' => $this->m('Have a BOQ or drawings? Get a priced quote within 48 hours.', 'عندك مقايسة أو رسومات؟ احصل على عرض سعر خلال 48 ساعة.'),
            'bullets' => [
                ['text' => $this->m('Project pricing on 10,000+ items', 'أسعار مشروعات على أكثر من 10,000 منتج')],
                ['text' => $this->m('Alternatives & value engineering', 'بدائل وهندسة قيمة')],
                ['text' => $this->m('Datasheets & IES files included', 'مع الداتا شيت وملفات IES')],
                ['text' => $this->m('Site delivery across Egypt', 'توصيل للموقع في كل مصر')],
            ],
            'hours_text' => $this->m('Sun–Thu 9:00–18:00 · reply within 2 working hours', 'الأحد–الخميس 9–6 · الرد خلال ساعتين عمل'),
            'button_label' => $this->m('Request a quote on WhatsApp', 'اطلب عرض سعر على واتساب'),
        ]);

        $megaPromo = $this->section('global', 'mega_promo', [
            'title' => $this->m('Free lighting design for projects', 'تصميم إضاءة مجاني للمشروعات'),
            'button_label' => $this->m('Book a consultation', 'احجز استشارة'),
            'button_link' => '/services',
        ]);
        if (! $megaPromo->hasMedia('section')) {
            $this->attachPhoto($megaPromo, 2, 'section', ['field' => 'image']);
        }

        // Footer form has only an input placeholder + "Join" button — no heading.
        // title reuses the footer placeholder; eyebrow has NO design copy (placeholder).
        $this->section('global', 'newsletter', [
            'eyebrow' => $this->m('Newsletter', 'النشرة البريدية'), // no design copy — placeholder
            'title' => $this->m('Email for offers & new arrivals', 'بريدك للعروض والجديد'),
        ]);
    }

    private function seedSettings(): void
    {
        $settings = app(Settings::class);

        $values = [
            // contact — Navbar.jsx strip + Footer.jsx. Phone/email/URLs have no
            // real design value (operational placeholders).
            'contact.whatsapp_number' => '1500060828',
            'contact.hotline' => '19XXX',                           // design shows "Hotline 19XXX" placeholder
            'contact.email' => 'hello@example.com',                 // placeholder
            'contact.maps_url' => 'https://maps.google.com',        // placeholder
            'contact.showroom_label' => $this->m('Showroom', 'المعرض'),
            'contact.showroom_address' => $this->m('New Cairo', 'القاهرة الجديدة'),
            'contact.working_hours' => $this->m('Sun–Thu 9:00–18:00', 'الأحد–الخميس 9:00–18:00'),
            'contact.social_instagram' => 'https://instagram.com',  // placeholder
            'contact.social_facebook' => 'https://facebook.com',    // placeholder
            'contact.social_linkedin' => 'https://linkedin.com',    // placeholder
            'contact.social_youtube' => 'https://youtube.com',      // placeholder

            // commerce — OrderSummary.jsx / Products.jsx + Phase 0 decisions.
            'commerce.instapay_address' => 'dots@instapay',         // placeholder
            'commerce.vat_rate' => 14,
            'commerce.vat_note' => $this->m('Prices include 14% VAT', 'الأسعار شاملة ضريبة القيمة المضافة 14%'),
            'commerce.project_pricing_note' => $this->m('Project pricing for 10+ units', 'أسعار مشروعات لـ 10 قطع فأكثر'),
            'commerce.low_stock_threshold' => 5,
            'commerce.instapay_hold_hours' => 24,
            'commerce.card_payment_window_minutes' => 60,
            'commerce.order_prefix' => 'DT-',
            'commerce.currency' => 'EGP',

            // whatsapp message templates — admin-editable (ERD). The design
            // builds these in code, so the default wording is ours; messages are
            // rendered via App\Services\WhatsApp by replacing {placeholders}.
            'whatsapp.tpl_product' => $this->m('Hi Dots, I’m interested in {product_name} ({sku}). {url}', 'مرحباً دوتس، مهتم بـ {product_name} ({sku}). {url}'),
            'whatsapp.tpl_cart' => $this->m("Hi Dots, I’d like to order my cart:\n{items}\nTotal: {total}", "مرحباً دوتس، أريد طلب السلة:\n{items}\nالإجمالي: {total}"),
            'whatsapp.tpl_instapay_order' => $this->m('Order {order_number}: I’ve transferred {total} via InstaPay and I’m sending the receipt.', 'طلب {order_number}: حوّلت {total} عبر إنستاباي وأرسل الإيصال.'),
            'whatsapp.tpl_boq' => $this->m('Hi Dots, I have a project BOQ to price. My name is {customer_name}.', 'مرحباً دوتس، عندي مقايسة مشروع لتسعيرها. اسمي {customer_name}.'),
            'whatsapp.tpl_general' => $this->m('Hi Dots, I have a question.', 'مرحباً دوتس، عندي سؤال.'),
            'whatsapp.tpl_order_confirm_admin' => $this->m('New order {order_number} from {customer_name} — total {total}.', 'طلب جديد {order_number} من {customer_name} — الإجمالي {total}.'),
            'whatsapp.tpl_order_update' => $this->m('Update on order {order_number}: {status}. Track it: {url}', 'تحديث على طلبك {order_number}: {status}. تتبعه: {url}'),

            // footer — Footer.jsx (verbatim).
            'footer.about_text' => $this->m('Lighting, smart home and electrical supply — with design, installation and after-sales support across Egypt.', 'إضاءة ومنزل ذكي ومستلزمات كهربائية — مع التصميم والتركيب وخدمة ما بعد البيع في كل مصر.'),
            'footer.payments_line' => $this->m('Cash on delivery · Visa · Mastercard · Meeza · InstaPay', 'الدفع عند الاستلام · فيزا · ماستركارد · ميزة · إنستاباي'),
            'footer.copyright' => $this->m('© 2026 Dots. All rights reserved.', '© 2026 Dots. جميع الحقوق محفوظة.'),

            // seo — no design copy (placeholders derived from the brand).
            'seo.default_title' => $this->m('Dots — Lighting, smart home & electrical', 'دوتس — إضاءة ومنزل ذكي وكهرباء'),
            'seo.default_description' => $this->m('Design, genuine products and certified installation across Egypt.', 'تصميم ومنتجات أصلية وتركيب معتمد في كل مصر.'),
        ];

        foreach ($values as $key => $value) {
            $settings->set($key, $value);
        }
    }
}
