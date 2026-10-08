<?php

namespace Database\Seeders;

use App\Models\PageSection;
use App\Services\Settings;
use Database\Seeders\Concerns\SeedsDesignMedia;
use Illuminate\Database\Seeder;

/**
 * page_sections (per config/page_sections.php) and settings defaults, from the
 * design copy. Long-form marketing copy is representative and will be fine-tuned
 * against the design while building the Phase 5 views.
 */
class SiteSeeder extends Seeder
{
    use SeedsDesignMedia;

    public function run(): void
    {
        $this->seedSections();
        $this->seedSettings();
    }

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

    private function seedSections(): void
    {
        $hero = $this->section('home', 'hero', [
            'eyebrow' => $this->m('Lighting · Smart home · Electrical', 'إضاءة · منزل ذكي · كهرباء'),
            'headline' => $this->m('Light that makes a house feel like home', 'إضاءة تحوّل المكان إلى بيت'),
            'subheadline' => $this->m('Design, genuine products and certified installation — from one accountable team.', 'تصميم ومنتجات أصلية وتركيب معتمد — من فريق واحد مسؤول.'),
            'button1_label' => $this->m('Shop lighting', 'تسوّق الإضاءة'),
            'button1_link' => '/products',
            'button2_label' => $this->m('Book a consultation', 'احجز استشارة'),
            'button2_link' => '/services',
        ]);
        if (! $hero->hasMedia('section')) {
            $this->attachFile($hero, 'heroVid.mp4', 'section');
            $this->attachPhoto($hero, 0, 'section', ['field' => 'poster']);
        }

        $this->section('home', 'features', ['items' => [
            ['icon' => 'truck', 'text' => $this->m('Fast delivery across Egypt', 'توصيل سريع لكل مصر')],
            ['icon' => 'shield-check', 'text' => $this->m('Official warranty on everything', 'ضمان رسمي على كل المنتجات')],
            ['icon' => 'badge-check', 'text' => $this->m('Genuine products only', 'منتجات أصلية فقط')],
            ['icon' => 'headset', 'text' => $this->m('Design & install support', 'دعم تصميم وتركيب')],
        ]]);

        $this->section('home', 'categories_head', [
            'eyebrow' => $this->m('Browse', 'تصفّح'),
            'title' => $this->m('Shop by category', 'تسوّق حسب القسم'),
            'button_label' => $this->m('All categories', 'كل الأقسام'),
        ]);

        $this->section('home', 'featured_head', [
            'eyebrow' => $this->m('Hand-picked', 'مختارة بعناية'),
            'title' => $this->m('Featured products', 'منتجات مميزة'),
            'tab_new_label' => $this->m('New arrivals', 'وصل حديثاً'),
            'tab_best_label' => $this->m('Best sellers', 'الأكثر مبيعاً'),
            'tab_smart_label' => $this->m('Smart home', 'منزل ذكي'),
        ]);

        $this->section('home', 'services_head', [
            'eyebrow' => $this->m('What we do', 'ماذا نقدّم'),
            'title' => $this->m('More than a shop', 'أكثر من متجر'),
            'button_label' => $this->m('Our services', 'خدماتنا'),
        ]);

        $smart = $this->section('home', 'smart_banner', [
            'eyebrow' => $this->m('Smart home', 'المنزل الذكي'),
            'title' => $this->m('One app for light, climate and security', 'تطبيق واحد للإضاءة والتكييف والأمان'),
            'text' => $this->m('Scenes, curtains and sensors — installed and programmed by our team.', 'مشاهد وستائر وحساسات — تركيب وبرمجة من فريقنا.'),
            'button1_label' => $this->m('Explore smart home', 'استكشف المنزل الذكي'),
            'button1_link' => '/products',
            'button2_label' => $this->m('Talk to us', 'تواصل معنا'),
            'button2_link' => '/services',
        ]);
        if (! $smart->hasMedia('section')) {
            $this->attachPhoto($smart, 17, 'section', ['field' => 'image']);
        }

        $this->section('home', 'brands_head', [
            'eyebrow' => $this->m('Trusted brands', 'علامات موثوقة'),
            'title' => $this->m('The names we work with', 'العلامات التي نعمل بها'),
            'button_label' => $this->m('All brands', 'كل العلامات'),
        ]);

        // About
        $aboutHero = $this->section('about', 'hero', [
            'eyebrow' => $this->m('About DOTS', 'عن دوتس'),
            'title' => $this->m('Lighting people, not just a shop', 'أهل إضاءة، لا مجرد متجر'),
            'text' => $this->m('We design, supply and install lighting and smart home systems across Egypt.', 'نصمم ونورد ونركب أنظمة الإضاءة والمنزل الذكي في كل مصر.'),
            'button1_label' => $this->m('Our services', 'خدماتنا'),
            'button1_link' => '/services',
            'button2_label' => $this->m('Our projects', 'مشاريعنا'),
            'button2_link' => '/projects',
        ]);
        if (! $aboutHero->hasMedia('section')) {
            $this->attachPhoto($aboutHero, 1, 'section', ['field' => 'image']);
        }

        $this->section('about', 'story', [
            'eyebrow' => $this->m('Our story', 'قصتنا'),
            'title' => $this->m('From a small counter to full projects', 'من كاونتر صغير إلى مشاريع كاملة'),
            'text' => $this->m('What started as a lighting counter is now a design, supply and installation team.', 'بدأنا كاونتر إضاءة وأصبحنا فريق تصميم وتوريد وتركيب.'),
        ]);

        $this->section('about', 'stats', ['items' => [
            ['value' => '12', 'suffix' => '+', 'label' => $this->m('Years', 'سنة')],
            ['value' => '3,500', 'suffix' => '+', 'label' => $this->m('Projects', 'مشروع')],
            ['value' => '40', 'suffix' => '+', 'label' => $this->m('Brands', 'علامة')],
            ['value' => '38', 'suffix' => '%', 'label' => $this->m('Avg. energy saved', 'متوسط توفير الطاقة')],
        ]]);

        $this->section('about', 'mission_vision', [
            'mission_text' => $this->m('Make great lighting simple to buy and install in Egypt.', 'نجعل الإضاءة الجيدة سهلة الشراء والتركيب في مصر.'),
            'vision_text' => $this->m('Be the most trusted lighting partner in the region.', 'أن نكون شريك الإضاءة الأكثر ثقة في المنطقة.'),
        ]);

        $this->section('about', 'disciplines', [
            'eyebrow' => $this->m('What we cover', 'ما نغطيه'),
            'title' => $this->m('Three disciplines, one team', 'ثلاثة تخصصات وفريق واحد'),
            'items' => [
                ['title' => $this->m('Lighting', 'الإضاءة'), 'text' => $this->m('Design and supply.', 'تصميم وتوريد.'), 'icon' => 'lamp-ceiling', 'link' => '/services'],
                ['title' => $this->m('Smart home', 'المنزل الذكي'), 'text' => $this->m('Control and automation.', 'تحكم وأتمتة.'), 'icon' => 'wifi', 'link' => '/services'],
                ['title' => $this->m('Electrical', 'الكهرباء'), 'text' => $this->m('Wiring and boards.', 'تمديدات ولوحات.'), 'icon' => 'plug-zap', 'link' => '/services'],
            ],
        ]);

        $this->section('about', 'why', [
            'eyebrow' => $this->m('Why DOTS', 'لماذا دوتس'),
            'title' => $this->m('Reasons clients stay with us', 'أسباب بقاء عملائنا معنا'),
            'items' => [
                ['icon' => 'badge-check', 'title' => $this->m('Genuine products', 'منتجات أصلية'), 'text' => $this->m('Official warranty.', 'ضمان رسمي.')],
                ['icon' => 'ruler', 'title' => $this->m('Real design', 'تصميم حقيقي'), 'text' => $this->m('Lux and renders.', 'حسابات وتصميمات.')],
                ['icon' => 'users', 'title' => $this->m('One team', 'فريق واحد'), 'text' => $this->m('Design to install.', 'من التصميم للتركيب.')],
                ['icon' => 'truck', 'title' => $this->m('Fast supply', 'توريد سريع'), 'text' => $this->m('One delivery.', 'توصيل واحد.')],
                ['icon' => 'wrench', 'title' => $this->m('Aftercare', 'ما بعد البيع'), 'text' => $this->m('We handle warranty.', 'نتابع الضمان.')],
                ['icon' => 'shield-check', 'title' => $this->m('Certified', 'معتمد'), 'text' => $this->m('Tested installs.', 'تركيب مختبر.')],
            ],
        ]);

        $this->section('about', 'partners_head', [
            'eyebrow' => $this->m('Partners', 'شركاء'),
            'title' => $this->m('Brands we represent', 'علامات نمثّلها'),
            'button_label' => $this->m('All brands', 'كل العلامات'),
        ]);

        $cta = $this->section('about', 'cta', [
            'title' => $this->m('Have a project in mind?', 'عندك مشروع في بالك؟'),
            'text' => $this->m('Tell us about your space and we will take it from there.', 'احكِ لنا عن مساحتك ونكمل نحن.'),
        ]);
        if (! $cta->hasMedia('section')) {
            $this->attachPhoto($cta, 34, 'section', ['field' => 'background_image']);
        }

        // Services
        $this->section('services', 'head', [
            'eyebrow' => $this->m('Services', 'خدمات'),
            'title' => $this->m('Design, supply and install', 'تصميم وتوريد وتركيب'),
            'intro' => $this->m('One accountable team from the first sketch to the final handover.', 'فريق واحد مسؤول من أول رسم حتى التسليم.'),
            'button_label' => $this->m('Book a consultation', 'احجز استشارة'),
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

        $this->section('brands', 'head', [
            'eyebrow' => $this->m('Brands', 'العلامات'),
            'title' => $this->m('The names we work with', 'العلامات التي نعمل بها'),
            'lede' => $this->m('Authorised distribution and official warranty across leading lighting and electrical brands.', 'توزيع معتمد وضمان رسمي لأهم علامات الإضاءة والكهرباء.'),
        ]);

        $currentHero = $this->section('projects', 'current_hero', [
            'eyebrow' => $this->m('In progress', 'جارية'),
            'title' => $this->m('Projects we are building now', 'مشاريع ننفّذها الآن'),
            'lede' => $this->m('Live sites across Egypt, updated as we go.', 'مواقع جارية في كل مصر، نحدّثها أولاً بأول.'),
            'button_label' => $this->m('See completed projects', 'شاهد المشاريع المنجزة'),
        ]);
        if (! $currentHero->hasMedia('section')) {
            $this->attachPhoto($currentHero, 38, 'section', ['field' => 'image']);
        }

        $this->section('projects', 'previous_head', [
            'eyebrow' => $this->m('Completed', 'منجزة'),
            'title' => $this->m('Selected work', 'أعمال مختارة'),
            'lede' => $this->m('A sample of what we have delivered.', 'عينة مما سلّمناه.'),
        ]);

        $this->section('blog', 'head', [
            'eyebrow' => $this->m('Journal', 'المدونة'),
            'title' => $this->m('Lighting guides & stories', 'أدلة وقصص الإضاءة'),
            'lede' => $this->m('Practical advice from our design and install teams.', 'نصائح عملية من فريق التصميم والتركيب.'),
        ]);

        // Global
        $this->section('global', 'quote_band', [
            'eyebrow' => $this->m('Projects & bulk', 'مشاريع وكميات'),
            'title' => $this->m('Have a BOQ or a big list?', 'عندك مقايسة أو قائمة كبيرة؟'),
            'bullets' => [
                ['text' => $this->m('Dedicated project pricing', 'أسعار مشاريع خاصة')],
                ['text' => $this->m('One delivery, one invoice', 'توصيل واحد وفاتورة واحدة')],
                ['text' => $this->m('Design support included', 'دعم تصميم مشمول')],
                ['text' => $this->m('Fast, named account manager', 'مدير حساب مخصص')],
            ],
            'hours_text' => $this->m('We reply within working hours, 10:00–18:00.', 'نرد خلال ساعات العمل، 10 ص – 6 م.'),
            'button_label' => $this->m('Send your BOQ on WhatsApp', 'أرسل المقايسة على واتساب'),
        ]);

        $megaPromo = $this->section('global', 'mega_promo', [
            'title' => $this->m('New: magnetic track systems', 'جديد: أنظمة تراك مغناطيسية'),
            'button_label' => $this->m('Shop now', 'تسوّق الآن'),
            'button_link' => '/products',
        ]);
        if (! $megaPromo->hasMedia('section')) {
            $this->attachPhoto($megaPromo, 7, 'section', ['field' => 'image']);
        }

        $this->section('global', 'newsletter', [
            'eyebrow' => $this->m('Stay in the loop', 'ابقَ على اطلاع'),
            'title' => $this->m('Lighting ideas in your inbox', 'أفكار إضاءة في بريدك'),
        ]);
    }

    private function seedSettings(): void
    {
        $settings = app(Settings::class);

        $values = [
            'contact.whatsapp_number' => '201000000000',
            'contact.hotline' => '19000',
            'contact.email' => 'hello@dots.eg',
            'contact.maps_url' => 'https://maps.google.com',
            'contact.showroom_label' => $this->m('Sheikh Zayed showroom', 'معرض الشيخ زايد'),
            'contact.showroom_address' => $this->m('Sheikh Zayed, Giza, Egypt', 'الشيخ زايد، الجيزة، مصر'),
            'contact.working_hours' => $this->m('Sat–Thu, 10:00–22:00', 'السبت–الخميس، 10 ص – 10 م'),
            'contact.social_instagram' => 'https://instagram.com',
            'contact.social_facebook' => 'https://facebook.com',
            'contact.social_linkedin' => 'https://linkedin.com',
            'contact.social_youtube' => 'https://youtube.com',

            'commerce.instapay_address' => 'dots@instapay',
            'commerce.vat_rate' => 14,
            'commerce.vat_note' => $this->m('Prices include 14% VAT.', 'الأسعار شاملة ضريبة القيمة المضافة 14%.'),
            'commerce.project_pricing_note' => $this->m('Project pricing available on request.', 'أسعار المشاريع متاحة عند الطلب.'),
            'commerce.low_stock_threshold' => 5,
            'commerce.instapay_hold_hours' => 24,
            'commerce.order_prefix' => 'DT-',
            'commerce.currency' => 'EGP',

            'whatsapp.tpl_product' => $this->m('Hi DOTS, I am interested in: {product}', 'مرحباً دوتس، مهتم بـ: {product}'),
            'whatsapp.tpl_cart' => $this->m('Hi DOTS, I would like to order my cart.', 'مرحباً دوتس، أريد طلب السلة.'),
            'whatsapp.tpl_instapay_order' => $this->m('Order {number}: sending my InstaPay receipt.', 'طلب {number}: أرسل إيصال إنستاباي.'),
            'whatsapp.tpl_boq' => $this->m('Hi DOTS, here is my BOQ for a project.', 'مرحباً دوتس، هذه مقايسة مشروعي.'),
            'whatsapp.tpl_general' => $this->m('Hi DOTS, I have a question.', 'مرحباً دوتس، عندي سؤال.'),

            'footer.about_text' => $this->m('DOTS designs, supplies and installs lighting, smart home and electrical systems across Egypt.', 'دوتس تصمم وتورد وتركب أنظمة الإضاءة والمنزل الذكي والكهرباء في كل مصر.'),
            'footer.payments_line' => $this->m('Cash on delivery · InstaPay · Card', 'الدفع عند الاستلام · إنستاباي · بطاقة'),
            'footer.copyright' => $this->m('© DOTS. All rights reserved.', '© دوتس. جميع الحقوق محفوظة.'),

            'seo.default_title' => $this->m('DOTS — Lighting, smart home & electrical', 'دوتس — إضاءة ومنزل ذكي وكهرباء'),
            'seo.default_description' => $this->m('Design, genuine products and certified installation across Egypt.', 'تصميم ومنتجات أصلية وتركيب معتمد في كل مصر.'),
        ];

        foreach ($values as $key => $value) {
            $settings->set($key, $value);
        }
    }
}
