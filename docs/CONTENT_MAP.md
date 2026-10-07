# DOTS — Content map

Every visible piece of content on every page → where it is stored → which admin screen edits it. **Nothing in this list may be hardcoded in Blade.** `page_sections page.key.field` and `settings group.key` refer to docs/ERD.md. "derived" = computed by the app. UI labels (buttons, form labels, errors) are not listed: they live in `lang/ar` and `lang/en`.

## Global (every page) (عناصر مشتركة)

| part | field | stored in | edited in admin |
|---|---|---|---|
| الشريط العلوي | الخط الساخن، المعرض، توصيل لكل المحافظات | settings `contact.hotline`<br>settings `contact.showroom_label`<br>settings `contact.util_strip_texts` | الإعدادات › التواصل |
| الشريط العلوي | رابط تتبع الطلب | derived: رابط ثابت لصفحة تتبع الطلب | — |
| الهيدر | اللوجو | derived: ملف ثابت في المشروع | — |
| الهيدر | عداد السلة | `cart_items.qty` | — |
| الهيدر | اسم العميل وقايمة الحساب | `customers.name` | — |
| الهيدر | قايمة المشروعات (جارية / سابقة) | lang file: عناوين ثابتة | — |
| Mega Menu | الأقسام الرئيسية + الأيقونة | `categories.name`<br>`categories.icon`<br>`categories.level` | الأقسام |
| Mega Menu | المجموعات والأقسام النهائية | `categories.parent_id`<br>`categories.sort_order` | الأقسام |
| Mega Menu | عدد المنتجات والبراندات | derived: عدد المنتجات والبراندات النشطة | — |
| Mega Menu | بانر الترويج (صورة، عنوان، زرار + رابط) | page_sections `global.mega_promo.image`<br>page_sections `global.mega_promo.title`<br>page_sections `global.mega_promo.button_label`<br>page_sections `global.mega_promo.button_link` | محتوى الصفحات › عام |
| شريط عرض السعر | العنوان، 4 نقاط، المواعيد | page_sections `global.quote_band.title`<br>page_sections `global.quote_band.bullets ×4`<br>page_sections `global.quote_band.hours_text` | محتوى الصفحات › عام |
| شريط عرض السعر | زرار واتساب + زرار المقايسة | `quote_requests.source`<br>settings `whatsapp.tpl_boq` | ضغطات واتساب |
| الفوتر | نص التعريف | settings `footer.about_text` | الإعدادات › الفوتر |
| الفوتر | عمود تسوق | `categories.show_in_footer` | الأقسام |
| الفوتر | عمود الخدمات | `services.show_in_footer` | الخدمات |
| الفوتر | عمود المساعدة (التوصيل، الاسترجاع، الضمان، الوظائف) | `pages.show_in_footer` | صفحات المحتوى |
| الفوتر | النشرة البريدية | `newsletter_subscribers.email` | المشتركين |
| الفوتر | سطر طرق الدفع وحقوق النشر | settings `footer.payments_line`<br>settings `footer.copyright` | الإعدادات › الفوتر |
| الفوتر | السوشيال | settings `contact.social_instagram`<br>settings `contact.social_facebook`<br>settings `contact.social_linkedin`<br>settings `contact.social_youtube` | الإعدادات › التواصل |
| SEO | عنوان ووصف كل صفحة | `categories.meta_title`<br>`products.meta_title`<br>settings `seo.default_title` | تاب SEO في كل فورم |

## Home (الرئيسية)

| part | field | stored in | edited in admin |
|---|---|---|---|
| Hero | العنوان، النص، الـ eyebrow، الزرارين، الفيديو والصورة | page_sections `home.hero.headline`<br>page_sections `home.hero.subheadline`<br>page_sections `home.hero.eyebrow`<br>page_sections `home.hero.button1_label`<br>page_sections `home.hero.button2_label`<br>page_sections `home.hero.video`<br>page_sections `home.hero.poster` | محتوى الصفحات › الرئيسية |
| شريط المميزات | 4 أيقونات ونصوص | page_sections `home.features.items[icon, text] ×4` | محتوى الصفحات › الرئيسية |
| تسوق حسب القسم | العنوان + الكروت (صورة، اسم، عدد) | page_sections `home.categories_head.title`<br>`categories.show_on_home`<br>`categories.image`<br>derived: عدد المنتجات | الأقسام |
| مختارات | العنوان + التابات الـ 3 | page_sections `home.featured_head.title`<br>`products.is_new`<br>`products.is_best_seller`<br>`products.is_smart_pick` | المنتجات › الشارات |
| الخدمات | العنوان + كروت الخدمات | page_sections `home.services_head.title`<br>`services.title`<br>`services.excerpt`<br>`services.image`<br>`services.number` | الخدمات |
| بانر المنزل الذكي | العنوان، النص، الزرارين، الصورة | page_sections `home.smart_banner.title`<br>page_sections `home.smart_banner.text`<br>page_sections `home.smart_banner.image` | محتوى الصفحات › الرئيسية |
| العلامات | العنوان + لوجوهات البراندات | page_sections `home.brands_head.title`<br>`brands.show_on_home`<br>`brands.logo`<br>`brands.origin_country`<br>derived: عدد المنتجات | العلامات |

## Products list (كل المنتجات)

| part | field | stored in | edited in admin |
|---|---|---|---|
| رأس الصفحة | Breadcrumbs، العنوان، العدد | `categories.name`<br>derived: عدد النتائج | — |
| الفلاتر | شجرة الأقسام بالعدد | `categories.parent_id`<br>derived: عدد لكل قسم (cache) | — |
| الفلاتر | الشركة المصنعة بالعدد | `brands.name`<br>`products.brand_id` | — |
| الفلاتر | درجة اللون، القدرة، التشطيب | `attributes.is_filterable`<br>`attribute_values.label`<br>`attribute_values.swatch`<br>`product_variant_values.attribute_value_id` | الخصائص |
| الفلاتر | نطاق السعر | `products.min_price` | — |
| الفلاتر | المتوفر فقط، شحن 24 ساعة | `products.stock_status`<br>`products.ships_24h` | المنتجات |
| الترتيب | المقترح، السعر، الأحدث | `products.sort_weight`<br>`products.min_price`<br>`products.published_at` | المنتجات |
| كارت المنتج | الصورة، البراند، الاسم، SKU، السعر، السعر القديم | `products.images`<br>`brands.name`<br>`products.name`<br>`products.sku`<br>`products.min_price`<br>`products.max_compare_at` | المنتجات |
| كارت المنتج | الشارات (خصم، جديد، مشروعات) | `products.max_compare_at`<br>`products.is_new`<br>`products.price_on_request` | المنتجات |
| كارت المنتج | نقط التشطيبات + ملخص الـ variants | `attribute_values.swatch`<br>`products.variants_summary` | المنتجات |
| كارت المنتج | حالة المخزون | `products.stock_status` | المنتجات (محسوب) |
| كارت المنتج | القلب (المفضلة) | `wishlists.product_id` | — |

## Category page (صفحة القسم)

| part | field | stored in | edited in admin |
|---|---|---|---|
| Hero | الصورة، الاسم، الوصف، عدد المنتجات | `categories.hero_image`<br>`categories.name`<br>`categories.description`<br>derived: العدد | الأقسام |
| الأقسام الفرعية | كروت بصورة وعدد | `categories.parent_id`<br>`categories.image` | الأقسام |
| الأكثر طلبًا | 4 منتجات | `category_featured_products.product_id` | الأقسام |
| دليل الشراء | العنوان + الأسئلة | `categories.buying_guide_title`<br>`faqs.question`<br>`faqs.answer` | الأقسام |
| دليل الشراء | شرايح البراندات | derived: براندات ليها منتجات في القسم | — |

## Product detail (تفاصيل المنتج)

| part | field | stored in | edited in admin |
|---|---|---|---|
| الصور | 5 صور + تعليق | `products.images` | المنتجات › الصور |
| المعلومات | البراند، الاسم، SKU، المخزون بالكمية، قابل للتعتيم | `brands.name`<br>`products.name`<br>`product_variants.sku`<br>`product_variants.stock_qty`<br>`products.is_dimmable` | المنتجات |
| السعر | السعر حسب الاختيار + القديم | `product_variants.price`<br>`product_variants.compare_at_price` | المنتجات › Variants |
| السعر | سطر الضريبة وأسعار المشروعات | settings `commerce.vat_note`<br>settings `commerce.project_pricing_note` | الإعدادات › البيع |
| الاختيارات | درجة اللون، القدرة، المقاس، التشطيب + غير المتاح | `attributes.display`<br>`attribute_values.label`<br>`product_variant_values.attribute_value_id`<br>`product_variants.is_active` | المنتجات › Variants |
| الأزرار | أضف للسلة، اطلب عرض سعر، المفضلة | `cart_items.product_variant_id`<br>`quote_requests.product_variant_id`<br>`wishlists.product_id` | — |
| شريط الثقة | التوصيل، التركيب، الضمان | settings `commerce.product_trust_items`<br>`products.warranty` | الإعدادات › البيع + المنتجات |
| التحميلات | داتا شيت، IES، LDT، دليل تركيب | `products.files` | المنتجات › الملفات |
| التابات | نظرة عامة | `products.overview` | المنتجات |
| التابات | المواصفات (اسم + وصف) | `product_specs.name`<br>`product_specs.value` | المنتجات › المواصفات |
| التابات | التوصيل والاسترجاع | settings `commerce.product_delivery_text`<br>settings `commerce.product_returns_text` | الإعدادات › البيع |
| غالبًا ما تُطلب معًا | 4 منتجات | `product_relations.related_id` | المنتجات › ذات صلة |

## Brands (العلامات التجارية)

| part | field | stored in | edited in admin |
|---|---|---|---|
| رأس الصفحة | eyebrow، العنوان، النص | page_sections `brands.head.eyebrow`<br>page_sections `brands.head.title`<br>page_sections `brands.head.lede` | محتوى الصفحات › العلامات |
| البحث وفلتر الحروف |  | `brands.name`<br>derived: الحروف من أسماء البراندات | — |
| العلامة المميزة | صورة، اسم، نص، زرار | `brands.is_featured`<br>`brands.feature_image`<br>`brands.feature_text` | العلامات |
| الشبكة | لوجو، اسم، بلد، عدد | `brands.logo`<br>`brands.origin_country`<br>derived: العدد | العلامات |

## Brand detail (تفاصيل العلامة)

| part | field | stored in | edited in admin |
|---|---|---|---|
| الهيدر | لوجو، شارة موزع معتمد، البلد، الاسم، الوصف، صورة | `brands.logo`<br>`brands.is_authorised`<br>`brands.origin_country`<br>`brands.description`<br>`brands.cover_image` | العلامات |
| الهيدر | زرار الكتالوج PDF | `brands.catalogue_file`<br>`brands.catalogue_label` | العلامات |
| الهيدر | زرار أسعار المشروعات | `quote_requests.sourceable_id` | ضغطات واتساب |
| المنتجات | تابات الأقسام بالعدد | derived: الأقسام الرئيسية اللي فيها منتجات البراند | — |
| من تنفيذ دوتس | 3 مشروعات | `project_product.product_id`<br>`projects.cover_image` | المشروعات |

## Services (الخدمات)

| part | field | stored in | edited in admin |
|---|---|---|---|
| رأس الصفحة | eyebrow، العنوان، المقدمة، زرار | page_sections `services.head.eyebrow`<br>page_sections `services.head.title`<br>page_sections `services.head.intro`<br>page_sections `services.head.button_label` | محتوى الصفحات › الخدمات |
| الكروت | رقم، عنوان، وصف، صورة | `services.number`<br>`services.title`<br>`services.excerpt`<br>`services.image` | الخدمات |
| كيف نعمل | 4 مراحل | page_sections `services.process.items[title, text] ×4` | محتوى الصفحات › الخدمات |
| الأسئلة الشائعة | العنوان + الأسئلة | page_sections `services.faq_head.title`<br>`faqs.question` | محتوى الصفحات › الخدمات |

## Service detail (تفاصيل الخدمة)

| part | field | stored in | edited in admin |
|---|---|---|---|
| الهيدر | رقم، عنوان، وصف، صورة | `services.number`<br>`services.title`<br>`services.description`<br>`services.image` | الخدمات |
| ماذا تشمل | العنوان + البنود + 3 صور | `services.included_title`<br>`service_items.text`<br>`services.gallery` | الخدمات |
| المراحل | العنوان + المراحل | `services.duration_text`<br>page_sections `services.process.items[title, text] ×4` | الخدمات |
| نموذج الاستشارة | الاسم، الموبايل، النوع، المساحة، الملاحظات | `consultation_requests.name`<br>`consultation_requests.mobile`<br>`consultation_requests.project_type`<br>`consultation_requests.area_range`<br>`consultation_requests.notes` | الاستشارات |
| الأسئلة | أسئلة الخدمة | `faqs.faqable_id` | الخدمات |

## Current projects (المشروعات الجارية)

| part | field | stored in | edited in admin |
|---|---|---|---|
| Hero | صورة، eyebrow، عنوان، نص، زرار | page_sections `projects.current_hero.image`<br>page_sections `projects.current_hero.title`<br>page_sections `projects.current_hero.lede` | محتوى الصفحات › المشروعات |
| الفلتر | أنواع المشروعات + عدد المواقع | `projects.type`<br>derived: العدد | — |
| المشروع الرئيسي | صورة، النوع، العنوان، الموقع، التقدم، المرحلة | `projects.is_featured`<br>`projects.cover_image`<br>`projects.location`<br>`projects.progress`<br>`projects.stage`<br>`projects.phase_label` | المشروعات |
| الكروت | باقي المشروعات | `projects.title`<br>`projects.scope_summary` | المشروعات |

## Previous projects (المشروعات السابقة)

| part | field | stored in | edited in admin |
|---|---|---|---|
| رأس الصفحة | eyebrow، العنوان، النص | page_sections `projects.previous_head.title`<br>page_sections `projects.previous_head.lede` | محتوى الصفحات › المشروعات |
| الفلتر | النوع + السنة | `projects.type`<br>`projects.completed_year` | — |
| الكروت | صورة، النوع، العنوان، الموقع، النطاق | `projects.cover_image`<br>`projects.title`<br>`projects.location`<br>`projects.scope_summary` | المشروعات |

## Project detail (تفاصيل المشروع)

| part | field | stored in | edited in admin |
|---|---|---|---|
| الهيدر | حالة، نوع، عنوان، مشاركة | `projects.status`<br>`projects.type`<br>`projects.title`<br>`projects.completed_year` | المشروعات |
| المعرض | الصور | `projects.gallery` | المشروعات |
| الجاري | عن المشروع + نطاق العمل | `projects.about` | المشروعات |
| المنجز | التحدي، الحل، النتيجة | `projects.challenge`<br>`projects.solution`<br>`projects.result` | المشروعات |
| المستخدم | الخدمات + المنتجات | `project_service.service_id`<br>`project_product.product_id` | المشروعات |
| السايدبار | التقدم والمراحل، الموقع، النوع، البدء، التسليم، العميل، النطاق | `projects.progress`<br>`projects.stage`<br>`projects.start_date`<br>`projects.expected_completion`<br>`projects.client`<br>`projects.scope_summary` | المشروعات |
| مشروعات أخرى | 3 مشروعات بنفس الحالة | derived: نفس الحالة، مرتبة | — |

## Blog (المدونة)

| part | field | stored in | edited in admin |
|---|---|---|---|
| رأس الصفحة | eyebrow، العنوان، النص | page_sections `blog.head.eyebrow`<br>page_sections `blog.head.title`<br>page_sections `blog.head.lede` | محتوى الصفحات › المدونة |
| المقال المميز |  | `posts.is_featured` | المدونة |
| التصنيفات + البحث |  | `post_categories.name`<br>`posts.title` | تصنيفات المدونة |
| الكروت | صورة، تصنيف، عنوان، ملخص، تاريخ، وقت القراءة | `posts.cover_image`<br>`posts.title`<br>`posts.excerpt`<br>`posts.published_at`<br>`posts.read_minutes` | المدونة |
| النشرة البريدية | العنوان + الإيميل | page_sections `global.newsletter.title`<br>`newsletter_subscribers.email` | محتوى الصفحات › عام |

## Article (المقال)

| part | field | stored in | edited in admin |
|---|---|---|---|
| الهيدر | التصنيف، العنوان، الملخص، الكاتب، الصورة، التاريخ، الوقت | `post_categories.name`<br>`posts.title`<br>`posts.excerpt`<br>`posts.author_name`<br>`posts.author_role`<br>`posts.author_photo`<br>`posts.published_at`<br>`posts.read_minutes` | المدونة |
| الصورة الكبيرة |  | `posts.cover_image` | المدونة |
| المحتوى | نص، عناوين، قوائم، صورة بتعليق، اقتباس، callout | `posts.body` | المدونة |
| الوسوم |  | `tags.name`<br>`post_tag.tag_id` | المدونة |
| السايدبار | منتجات المقال | `post_product.product_id` | المدونة |
| مقالات ذات صلة |  | derived: نفس التصنيف | — |

## About (من نحن)

| part | field | stored in | edited in admin |
|---|---|---|---|
| Hero | صورة/فيديو، eyebrow، عنوان، نص، زرارين + رابطيهما | page_sections `about.hero.image`<br>page_sections `about.hero.video`<br>page_sections `about.hero.title`<br>page_sections `about.hero.text`<br>page_sections `about.hero.button1_link`<br>page_sections `about.hero.button2_link` | محتوى الصفحات › من نحن |
| قصتنا | عنوان، نص، 3 صور | page_sections `about.story.title`<br>page_sections `about.story.text`<br>page_sections `about.story.images ×3` | محتوى الصفحات › من نحن |
| الأرقام | 4 أرقام | page_sections `about.stats.items[value, suffix, label] ×4` | محتوى الصفحات › من نحن |
| المهمة والرؤية | نص + صورة لكل واحدة | page_sections `about.mission_vision.mission_text`<br>page_sections `about.mission_vision.mission_image`<br>page_sections `about.mission_vision.vision_text`<br>page_sections `about.mission_vision.vision_image` | محتوى الصفحات › من نحن |
| التخصصات | 3 كروت | page_sections `about.disciplines.items[title, text, icon, image, link] ×3` | محتوى الصفحات › من نحن |
| لماذا دوتس | 6 نقاط | page_sections `about.why.items[icon, title, text] ×6` | محتوى الصفحات › من نحن |
| الشركاء | عنوان + لوجوهات | page_sections `about.partners_head.title`<br>`brands.show_in_partners` | محتوى الصفحات + العلامات |
| الـ CTA | عنوان، نص، خلفية | page_sections `about.cta.title`<br>page_sections `about.cta.text`<br>page_sections `about.cta.background_image` | محتوى الصفحات › من نحن |

## Cart (السلة)

| part | field | stored in | edited in admin |
|---|---|---|---|
| السطور | صورة، اسم، variant، سعر، كمية | `cart_items.product_variant_id`<br>`cart_items.qty` | — |
| عرض سعر للسلة | واتساب بمحتوى السلة | `quote_requests.cart_snapshot`<br>settings `whatsapp.tpl_cart` | ضغطات واتساب |
| الملخص | الإجمالي، كود الخصم، سطر الضريبة | `coupons.code`<br>settings `commerce.vat_note` | الكوبونات |
| لا تنسى | 4 منتجات | `product_relations.type` | المنتجات › ذات صلة |

## Checkout (إتمام الشراء)

| part | field | stored in | edited in admin |
|---|---|---|---|
| سطر الحساب | عندك حساب؟ / مسجل باسم | `customers.name` | — |
| التواصل | الاسم، الموبايل، الإيميل، طلب شركة + الرقم الضريبي | `orders.contact_name`<br>`orders.contact_mobile`<br>`orders.contact_email`<br>`orders.is_company`<br>`orders.company_tax_id` | الطلبات |
| العنوان | عناوين محفوظة أو عنوان جديد | `customer_addresses.label`<br>`order_addresses.governorate_id`<br>`order_addresses.city`<br>`order_addresses.street`<br>`order_addresses.floor_apartment`<br>`order_addresses.landmark` | — |
| التوصيل | الطرق اللي الأدمن عاملها بأسعارها | `delivery_methods.name`<br>`delivery_methods.description`<br>`delivery_methods.price`<br>`delivery_methods.free_over_amount`<br>`delivery_method_governorate.governorate_id` | طرق التوصيل |
| الدفع | الطرق المفعّلة | `payment_methods.name`<br>`payment_methods.description`<br>`payment_methods.is_active` | طرق الدفع |
| الملخص | الكوبون والإجمالي | `orders.coupon_code`<br>`orders.total` | — |

## Order confirmation (تأكيد الطلب)

| part | field | stored in | edited in admin |
|---|---|---|---|
| الهيدر | رقم الطلب، التاريخ، حالة الدفع | `orders.number`<br>`orders.placed_at`<br>`orders.payment_status` | الطلبات |
| احفظ بياناتك | باسورد واحد (Guest بس) | `customers.password`<br>`orders.customer_id` | — |
| الخطوات التالية | حسب طريقة الدفع | `payment_methods.instructions`<br>`orders.reserved_until` | طرق الدفع |
| التفاصيل | التوصيل، العنوان، الدفع (آخر 4 أرقام) | `orders.delivery_method_name`<br>`order_addresses.recipient_name`<br>`payments.card_last4`<br>`payments.transaction_id` | الطلبات |
| الملخص | المنتجات، SKU، الكوبون، الإجمالي | `order_items.product_name`<br>`order_items.sku`<br>`orders.coupon_code`<br>`orders.discount_total`<br>`orders.delivery_fee`<br>`orders.total` | الطلبات |

## Payment failed (فشل الدفع)

| part | field | stored in | edited in admin |
|---|---|---|---|
| سبب الرفض | الكود والرسالة | `payments.failure_code`<br>`payments.failure_message` | الطلبات › سجل Paymob |
| البدائل | إعادة المحاولة، COD، InstaPay | `payment_methods.code`<br>`orders.payment_method_id` | — |

## Track order (تتبع الطلب)

| part | field | stored in | edited in admin |
|---|---|---|---|
| البحث | رقم الطلب (بيتولد تلقائي) + الموبايل | `orders.number`<br>`orders.contact_mobile` | — |
| النتيجة | الحالة، حالة الدفع، الخط الزمني بالأوقات والملاحظات | `orders.status`<br>`orders.payment_status`<br>`order_status_histories.created_at`<br>`order_status_histories.note` | الطلبات › تغيير الحالة |
| الموعد المتوقع | تاريخ اختياري | `orders.expected_delivery_at` | الطلبات |

## Content page (صفحة المحتوى)

| part | field | stored in | edited in admin |
|---|---|---|---|
| الكل | العنوان، النص، آخر تحديث، المحتوى، القايمة الجانبية | `pages.title`<br>`pages.lede`<br>`pages.updated_at`<br>`pages.body`<br>`pages.sort_order` | صفحات المحتوى |
| صندوق المساعدة | واتساب + الخط الساخن + المواعيد | settings `contact.whatsapp_number`<br>settings `contact.hotline`<br>settings `contact.working_hours` | الإعدادات › التواصل |

## Login & register (الدخول والتسجيل)

| part | field | stored in | edited in admin |
|---|---|---|---|
| الدخول | موبايل أو إيميل + باسورد + تذكرني | `customers.mobile`<br>`customers.email`<br>`customers.password`<br>`customers.remember_token` | — |
| التسجيل | الاسم، الموبايل، الإيميل، الباسورد | `customers.name`<br>`customers.mobile`<br>`customers.email`<br>`customers.password` | العملاء |
| التسجيل | روابط شروط الاستخدام وسياسة الخصوصية | `pages.key` | صفحات المحتوى |
| نسيت / تعيين الباسورد | لينك بالإيميل 30 دقيقة | `customer_password_reset_tokens.token` | — |

## Account area (حسابي)

| part | field | stored in | edited in admin |
|---|---|---|---|
| السايدبار | الاسم، الإيميل، الأعداد | `customers.name`<br>`customers.email` | — |
| نظرة عامة | عضو منذ، آخر طلب، المفضلة | `customers.created_at`<br>`orders.customer_id`<br>`wishlists.product_id` | — |
| طلباتي | رقم، تاريخ، عدد، إجمالي، الحالتين، صور | `orders.number`<br>`orders.placed_at`<br>`orders.total`<br>`orders.status`<br>`orders.payment_status`<br>`order_items.image_url` | — |
| تفاصيل الطلب | الخط الزمني، المنتجات، الدفع، العنوان، إعادة الطلب | `order_status_histories.note`<br>`order_items.variant_label`<br>`order_addresses.street`<br>`cart_items.product_variant_id` | — |
| المفضلة | المنتجات + أضف الكل للسلة | `wishlists.product_id` | العملاء › التفاصيل |
| العناوين | تسمية، مستلم، موبايل، محافظة، مدينة، شارع، دور، علامة، افتراضي | `customer_addresses.label`<br>`customer_addresses.recipient_name`<br>`customer_addresses.mobile`<br>`customer_addresses.governorate_id`<br>`customer_addresses.city`<br>`customer_addresses.street`<br>`customer_addresses.floor_apartment`<br>`customer_addresses.landmark`<br>`customer_addresses.is_default` | العملاء › التفاصيل |
| البروفايل | تعديل البيانات + تغيير الباسورد | `customers.name`<br>`customers.mobile`<br>`customers.email`<br>`customers.password` | — |
