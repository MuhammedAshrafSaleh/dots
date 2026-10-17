# DOTS — Database design (ERD)

Approved by the client owner. Source of truth for migrations. Notes are partly Arabic (they were reviewed in Arabic).

Conventions: every `json · translatable` column uses spatie/laravel-translatable with keys `ar` and `en`. Money is `decimal(12,2)`, VAT (14%) is included in prices. `media collection` = spatie/laravel-medialibrary, no column. Add `foreignId()->constrained()` with sensible `onDelete` (restrict for catalog refs from orders; cascade for pivots/children). `slug` translatable = one slug per locale, unique per locale.

Slug enforcement & routing: per-locale uniqueness and route-model binding use MySQL **generated STORED** columns `slug_ar` / `slug_en` extracted from the translatable `slug` JSON, each with a `UNIQUE` index, backed by app-level validation. Slugs stay editable in the admin. The Arabic slugger preserves Arabic script (Unicode-preserving) and appends `-2` / `-3` … on collision. (MySQL 8.0-compatible: no 9.x-only syntax.)

## Relations overview

**Catalog**
- `categories.parent_id` → `categories`
- `products.brand_id` → `brands`
- `products.category_id` → `categories`
- `product_variants.product_id` → `products`
- `attribute_values.attribute_id` → `attributes`
- `product_variant_values.product_variant_id` → `product_variants`
- `product_variant_values.attribute_value_id` → `attribute_values`
- `product_specs.product_id` → `products`
- `product_relations.product_id` → `products`
- `product_relations.related_id` → `products`
- `category_featured_products.category_id` → `categories`
- `category_featured_products.product_id` → `products`

**Content**
- `service_items.service_id` → `services`
- `project_product.project_id` → `projects`
- `project_product.product_id` → `products`
- `project_service.project_id` → `projects`
- `project_service.service_id` → `services`
- `posts.post_category_id` → `post_categories`
- `post_tag.post_id` → `posts`
- `post_tag.tag_id` → `tags`
- `post_product.post_id` → `posts`
- `post_product.product_id` → `products`

**Commerce & orders**
- `delivery_method_governorate.delivery_method_id` → `delivery_methods`
- `delivery_method_governorate.governorate_id` → `governorates`
- `coupon_redemptions.coupon_id` → `coupons`
- `coupon_redemptions.order_id` → `orders`
- `coupon_redemptions.customer_id` → `customers`
- `carts.customer_id` → `customers`
- `cart_items.cart_id` → `carts`
- `cart_items.product_variant_id` → `product_variants`
- `orders.customer_id` → `customers`
- `orders.payment_method_id` → `payment_methods`
- `orders.delivery_method_id` → `delivery_methods`
- `orders.coupon_id` → `coupons`
- `order_items.order_id` → `orders`
- `order_items.product_id` → `products`
- `order_items.product_variant_id` → `product_variants`
- `order_addresses.order_id` → `orders`
- `order_addresses.governorate_id` → `governorates`
- `order_status_histories.order_id` → `orders`
- `order_status_histories.user_id` → `users`
- `payments.order_id` → `orders`

**Customers**
- `customer_addresses.customer_id` → `customers`
- `customer_addresses.governorate_id` → `governorates`
- `wishlists.customer_id` → `customers`
- `wishlists.product_id` → `products`

**Leads**
- `quote_requests.product_variant_id` → `product_variants`
- `quote_requests.order_id` → `orders`
- `quote_requests.customer_id` → `customers`
- `consultation_requests.service_id` → `services`

## Catalog

### `categories` — شجرة 3 مستويات، مشتركة بين كل البراندات
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `parent_id` | FK → categories | null = قسم رئيسي |
| `level` | tinyint | 1 رئيسي · 2 مجموعة · 3 نهائي |
| `name` | json · translatable |  |
| `slug` | json · translatable | slug لكل لغة |
| `description` | json · translatable | نص الـ Hero في صفحة القسم |
| `icon` | string | اسم أيقونة Lucide (للقسم الرئيسي في الـ Mega Menu) |
| `image` | media collection `image` (not a column) | صورة الكارت |
| `hero_image` | media collection `hero` (not a column) | خلفية صفحة القسم |
| `buying_guide_title` | json · translatable | عنوان دليل الشراء |
| `show_on_home` | boolean | يظهر في "تسوق حسب القسم" |
| `show_in_footer` | boolean | عمود Shop في الفوتر |
| `sort_order` | int |  |
| `is_active` | boolean |  |
| `meta_title` | json · translatable | SEO |
| `meta_description` | json · translatable | SEO |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `parent_id`, `(is_active, sort_order)`

### `brands`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `name` | string | اسم البراند مش بيتترجم |
| `slug` | string | unique |
| `origin_country` | json · translatable |  |
| `description` | json · translatable | نص صفحة البراند |
| `feature_text` | json · translatable | نص بانر "علامة مميزة" |
| `logo` | media collection `logo` (not a column) |  |
| `cover_image` | media collection `cover` (not a column) | صورة هيدر صفحة البراند |
| `feature_image` | media collection `feature` (not a column) | صورة بانر العلامة المميزة |
| `catalogue_file` | media collection `catalogue` (not a column) | PDF |
| `catalogue_label` | json · translatable | مثال: كتالوج 2026 |
| `is_featured` | boolean | واحدة بس في بانر صفحة العلامات |
| `is_authorised` | boolean | شارة "موزع معتمد" |
| `show_in_partners` | boolean | شريط الشركاء في من نحن |
| `show_on_home` | boolean | قسم العلامات في الرئيسية |
| `sort_order` | int |  |
| `is_active` | boolean |  |
| `meta_title` | json · translatable | SEO |
| `meta_description` | json · translatable | SEO |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `slug unique`, `is_featured`, `(is_active, sort_order)`

### `products`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `brand_id` | FK → brands |  |
| `category_id` | FK → categories | قسم نهائي (level 3) |
| `name` | json · translatable |  |
| `slug` | json · translatable |  |
| `sku` | string | الكود الأساسي، unique |
| `overview` | json · translatable rich HTML (sanitized) | تاب نظرة عامة |
| `variants_summary` | json · translatable | مثال: 3 تشطيبات · درجتين (لو فاضي بيتولد لوحده) |
| `warranty` | json · translatable | مثال: 3 سنوات |
| `price_on_request` | boolean | يخفي السعر والسلة ويخلي واتساب هو الأساسي |
| `min_price` | decimal(12,2) | cache من الـ variants للترتيب والفلتر |
| `max_compare_at` | decimal(12,2) | cache لشارة الخصم |
| `stock_status` | enum(in, low, order) | cache محسوب من الـ variants |
| `is_new` | boolean | تاب وصل حديثًا + شارة جديد |
| `is_best_seller` | boolean | تاب الأكثر مبيعًا |
| `is_smart_pick` | boolean | تاب منزل ذكي في الرئيسية |
| `is_dimmable` | boolean | شارة قابل للتعتيم |
| `ships_24h` | boolean | فلتر شحن خلال 24 ساعة |
| `sort_weight` | int | ترتيب "المقترح" |
| `search_text` | text | FULLTEXT: الاسم عربي+إنجليزي + SKUs + البراند |
| `status` | enum(draft, active, archived) |  |
| `published_at` | timestamp |  |
| `images` | media collection `gallery` (not a column) | مع caption مترجم كـ custom property |
| `files` | media collection `files` (not a column) | داتا شيت / IES / LDT / دليل تركيب، مع title ونوع |
| `meta_title` | json · translatable | SEO |
| `meta_description` | json · translatable | SEO |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `sku unique`, `(status, category_id)`, `brand_id`, `min_price`, `stock_status`, `created_at`, `sort_weight`, `FULLTEXT(search_text)`

### `product_variants`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `product_id` | FK → products |  |
| `sku` | string | unique، بيتنسخ في سطر الطلب |
| `price` | decimal(12,2) | null لو السعر عند الطلب |
| `compare_at_price` | decimal(12,2) | السعر قبل الخصم |
| `stock_qty` | int |  |
| `low_stock_threshold` | int | null = الإعداد العام |
| `is_default` | boolean |  |
| `is_active` | boolean |  |
| `sort_order` | int |  |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `sku unique`, `product_id`, `price`

### `attributes`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `code` | string | cct · wattage · size · finish |
| `name` | json · translatable |  |
| `display` | enum(chip, swatch, button) | شكل الاختيار والفلتر |
| `unit` | string | K · W · mm |
| `is_filterable` | boolean |  |
| `is_variant_axis` | boolean |  |
| `sort_order` | int |  |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `code unique`

### `attribute_values`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `attribute_id` | FK → attributes |  |
| `value` | string | مثال: 3000 |
| `label` | json · translatable | مثال: أبيض دافئ |
| `swatch` | string | لون أو gradient للتشطيب ودرجة اللون |
| `sort_order` | int |  |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `(attribute_id, value) unique`

### `product_variant_values` — pivot: بيغذي الاختيارات والفلاتر
| column | type | notes |
|---|---|---|
| `product_variant_id` | FK → product_variants |  |
| `attribute_value_id` | FK → attribute_values |  |

Indexes: `PK(product_variant_id, attribute_value_id)`, `attribute_value_id`

### `product_specs` — جدول ثابت لكل منتج: اسم + وصف، من غير مجموعات
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `product_id` | FK → products |  |
| `name` | json · translatable | اسم المواصفة، مثال: الفيض الضوئي |
| `value` | json · translatable | الوصف، مثال: 2,450 lm |
| `sort_order` | int |  |

Indexes: `product_id`

### `product_relations`
| column | type | notes |
|---|---|---|
| `product_id` | FK → products |  |
| `related_id` | FK → products |  |
| `type` | enum(together, cart_suggestion) | "غالبًا ما تُطلب معًا" · "لا تنسى" في السلة |
| `sort_order` | int |  |

Indexes: `PK(product_id, related_id, type)`

### `category_featured_products` — "الأكثر طلبًا هذا الموسم" في صفحة القسم
| column | type | notes |
|---|---|---|
| `category_id` | FK → categories |  |
| `product_id` | FK → products |  |
| `sort_order` | int |  |

Indexes: `PK(category_id, product_id)`

## Content

### `services`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `number` | string | 01 |
| `title` | json · translatable |  |
| `slug` | json · translatable |  |
| `excerpt` | json · translatable | وصف الكارت |
| `description` | json · translatable | نص الهيدر في صفحة الخدمة |
| `included_title` | json · translatable | مثال: باقة كاملة قابلة للتنفيذ |
| `duration_text` | json · translatable | مثال: من الفكرة للتسليم خلال 3–6 أسابيع |
| `image` | media collection `image` (not a column) |  |
| `gallery` | media collection `gallery` (not a column) | 3 صور جنب ماذا تشمل |
| `show_in_footer` | boolean |  |
| `sort_order` | int |  |
| `is_active` | boolean |  |
| `meta_title` | json · translatable | SEO |
| `meta_description` | json · translatable | SEO |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `(is_active, sort_order)`

### `service_items`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `service_id` | FK → services |  |
| `text` | json · translatable | بند في "ماذا تشمل" |
| `sort_order` | int |  |

Indexes: `service_id`

### `projects` — من غير Before/After
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `status` | enum(current, completed) |  |
| `type` | enum(residential, commercial, outdoor, smart_home, hospitality) | فلتر النوع |
| `title` | json · translatable |  |
| `slug` | json · translatable |  |
| `location` | json · translatable |  |
| `client` | json · translatable | null = عميل خاص |
| `start_date` | date |  |
| `expected_completion` | date |  |
| `completed_year` | int |  |
| `progress` | tinyint | 0–100 |
| `stage` | enum(design, first_fix, installation, commissioning, handover) | المرحلة في شريط التقدم |
| `phase_label` | json · translatable | مثال: التشطيب النهائي والوحدات |
| `scope_summary` | json · translatable | سطر النطاق في الكارت والسايدبار |
| `about` | json · translatable rich HTML (sanitized) | عن المشروع + نطاق العمل |
| `challenge` | json · translatable | للمنجز |
| `solution` | json · translatable | للمنجز |
| `result` | json · translatable | للمنجز |
| `cover_image` | media collection `cover` (not a column) |  |
| `gallery` | media collection `gallery` (not a column) | مرتب بالسحب |
| `is_featured` | boolean | المشروع الكبير أول صفحة الجارية |
| `sort_order` | int |  |
| `is_active` | boolean |  |
| `meta_title` | json · translatable | SEO |
| `meta_description` | json · translatable | SEO |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `(status, is_active, sort_order)`, `type`, `completed_year`

### `project_product` — المنتجات المستخدمة + "من تنفيذ دوتس" في صفحة البراند
| column | type | notes |
|---|---|---|
| `project_id` | FK → projects |  |
| `product_id` | FK → products |  |

Indexes: `PK(project_id, product_id)`, `product_id`

### `project_service`
| column | type | notes |
|---|---|---|
| `project_id` | FK → projects |  |
| `service_id` | FK → services |  |

Indexes: `PK(project_id, service_id)`

### `post_categories`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `name` | json · translatable |  |
| `slug` | json · translatable |  |
| `sort_order` | int |  |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

### `posts`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `post_category_id` | FK → post_categories |  |
| `title` | json · translatable |  |
| `slug` | json · translatable |  |
| `excerpt` | json · translatable |  |
| `body` | json · translatable rich HTML (sanitized) | عناوين، قوائم، اقتباس، صورة بتعليق، callout |
| `cover_image` | media collection `cover` (not a column) |  |
| `author_name` | json · translatable | اختياري، الافتراضي: فريق تحرير دوتس |
| `author_role` | json · translatable |  |
| `author_photo` | media collection `author` (not a column) |  |
| `read_minutes` | tinyint |  |
| `is_featured` | boolean | المقال الكبير أول المدونة |
| `status` | enum(draft, published, scheduled) |  |
| `published_at` | timestamp |  |
| `meta_title` | json · translatable | SEO |
| `meta_description` | json · translatable | SEO |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `(status, published_at)`, `post_category_id`, `is_featured`

### `tags`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `name` | json · translatable |  |
| `slug` | string | unique |

### `post_tag`
| column | type | notes |
|---|---|---|
| `post_id` | FK → posts |  |
| `tag_id` | FK → tags |  |

Indexes: `PK(post_id, tag_id)`

### `post_product` — "تسوق منتجات هذا المقال"
| column | type | notes |
|---|---|---|
| `post_id` | FK → posts |  |
| `product_id` | FK → products |  |
| `sort_order` | int |  |

Indexes: `PK(post_id, product_id)`

### `pages` — "آخر تحديث" = updated_at
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `key` | string | delivery · returns · warranty · careers · terms · privacy |
| `title` | json · translatable |  |
| `slug` | json · translatable |  |
| `lede` | json · translatable |  |
| `body` | json · translatable rich HTML (sanitized) | h2 · h3 · p · ul · ol · callout |
| `show_in_footer` | boolean |  |
| `sort_order` | int |  |
| `is_active` | boolean |  |
| `meta_title` | json · translatable | SEO |
| `meta_description` | json · translatable | SEO |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `key unique`

### `page_sections` — كل محتوى الصفحات الثابتة اللي بيتعدل من اللوحة
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `page` | string | home · about · services · brands · projects · blog · global |
| `key` | string | مثال: hero · stats · why · quote_band |
| `content` | json | حقول مترجمة حسب schema ثابت في الكود لكل section |
| `images` | media collection `section` (not a column) | صور وفيديو الـ section |
| `is_active` | boolean |  |
| `sort_order` | int |  |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `(page, key) unique`

### `faqs`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `faqable_type` | string | category · service · null = أسئلة صفحة الخدمات العامة |
| `faqable_id` | int |  |
| `question` | json · translatable |  |
| `answer` | json · translatable |  |
| `sort_order` | int |  |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `(faqable_type, faqable_id, sort_order)`

### `settings`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `group` | string | contact · commerce · whatsapp · seo · footer |
| `key` | string |  |
| `value` | json | قيمة أو نص مترجم |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `(group, key) unique`

## Commerce & orders

### `governorates` — 27 محافظة seed
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `name` | json · translatable |  |
| `sort_order` | int |  |
| `is_active` | boolean |  |

### `delivery_methods` — مرنة بالكامل: الأدمن بيضيف ويعدّل أي طريقة، ومفيش طرق ثابتة في الكود
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `name` | json · translatable | الأدمن بيكتبه |
| `description` | json · translatable | الأدمن بيكتبه |
| `note` | json · translatable | سطر اختياري حر |
| `icon` | string |  |
| `price` | decimal(12,2) |  |
| `price_is_from` | boolean | يظهر "يبدأ من" |
| `free_over_amount` | decimal(12,2) | مجاني فوق مبلغ يحدده الأدمن (فاضي = لا) |
| `requires_address` | boolean | اقفله لو الطريقة استلام من المعرض |
| `sort_order` | int |  |
| `is_active` | boolean |  |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `(is_active, sort_order)`

### `delivery_method_governorate` — فاضي = كل المحافظات
| column | type | notes |
|---|---|---|
| `delivery_method_id` | FK → delivery_methods |  |
| `governorate_id` | FK → governorates |  |

Indexes: `PK(delivery_method_id, governorate_id)`

### `payment_methods`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `code` | enum(cod, card, instapay_whatsapp) |  |
| `name` | json · translatable |  |
| `description` | json · translatable |  |
| `instructions` | json · translatable | تظهر للعميل |
| `icon` | string |  |
| `sort_order` | int |  |
| `is_active` | boolean | البطاقة بتختفي لو مفاتيح Paymob فاضية |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `code unique`

### `coupons`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `code` | string | unique، بيتكتب يدوي |
| `type` | enum(percent, fixed) |  |
| `value` | decimal(12,2) |  |
| `min_order_total` | decimal(12,2) |  |
| `starts_at` | timestamp |  |
| `ends_at` | timestamp |  |
| `usage_limit` | int | null = مفتوح |
| `used_count` | int |  |
| `once_per_customer` | boolean | للعملاء المسجلين |
| `is_active` | boolean |  |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `code unique`, `(is_active, ends_at)`

### `coupon_redemptions`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `coupon_id` | FK → coupons |  |
| `order_id` | FK → orders |  |
| `customer_id` | FK → customers | null للـ Guest |
| `amount` | decimal(12,2) |  |
| `created_at` | timestamp |  |

Indexes: `(coupon_id, customer_id)`, `order_id`

### `carts` — بيتدمج لما العميل يدخل
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `customer_id` | FK → customers | null للـ Guest |
| `session_id` | string |  |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `customer_id`, `session_id`

### `cart_items`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `cart_id` | FK → carts |  |
| `product_variant_id` | FK → product_variants |  |
| `qty` | int |  |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `(cart_id, product_variant_id) unique`

### `orders`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `number` | string | `settings.commerce.order_prefix` (افتراضي `DT-`) + `orders.id`؛ الـ AUTO_INCREMENT يبدأ من 10001؛ يتولد ويُخزن مرة واحدة وقت الإنشاء داخل نفس الـ transaction، unique، وهو رقم التتبع. تغيير البادئة يؤثر على الطلبات الجديدة فقط |
| `customer_id` | FK → customers | null = Guest |
| `contact_name` | string |  |
| `contact_mobile` | string |  |
| `contact_email` | string | اختياري |
| `is_company` | boolean | طلب شركة / مشروع |
| `company_tax_id` | string |  |
| `locale` | enum(ar, en) |  |
| `status` | enum(new, confirmed, preparing, shipped, delivered, cancelled) |  |
| `payment_status` | enum(pending, awaiting_transfer, paid, failed, refunded) |  |
| `payment_method_id` | FK → payment_methods |  |
| `payment_method_code` | string | snapshot |
| `delivery_method_id` | FK → delivery_methods |  |
| `delivery_method_name` | json · translatable | snapshot |
| `subtotal` | decimal(12,2) |  |
| `discount_total` | decimal(12,2) |  |
| `coupon_id` | FK → coupons |  |
| `coupon_code` | string | snapshot |
| `delivery_fee` | decimal(12,2) |  |
| `total` | decimal(12,2) |  |
| `vat_rate` | decimal(12,2) | 14 snapshot |
| `vat_amount` | decimal(12,2) | مشمول في الإجمالي |
| `expected_delivery_at` | date | اختياري، الأدمن بيحدده |
| `reserved_until` | timestamp | حجز 24 ساعة لطلب InstaPay |
| `admin_notes` | text |  |
| `ip` | string |  |
| `user_agent` | string |  |
| `placed_at` | timestamp |  |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `number unique`, `customer_id`, `contact_mobile`, `(status, placed_at)`, `payment_status`

### `order_items`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `order_id` | FK → orders |  |
| `product_id` | FK → products | null لو المنتج اتمسح |
| `product_variant_id` | FK → product_variants |  |
| `product_name` | json · translatable | snapshot |
| `variant_label` | json · translatable | snapshot: أسود مطفي · 3000K |
| `sku` | string | snapshot |
| `image_url` | string | snapshot |
| `unit_price` | decimal(12,2) |  |
| `qty` | int |  |
| `line_total` | decimal(12,2) |  |

Indexes: `order_id`, `product_id`

### `order_addresses`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `order_id` | FK → orders | 1:1 |
| `recipient_name` | string |  |
| `mobile` | string |  |
| `governorate_id` | FK → governorates |  |
| `governorate_name` | json · translatable | snapshot |
| `city` | string |  |
| `street` | string |  |
| `floor_apartment` | string |  |
| `landmark` | string | اختياري |

Indexes: `order_id unique`

### `order_status_histories`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `order_id` | FK → orders |  |
| `status` | string | حالة الطلب أو الدفع |
| `note` | json · translatable | ملاحظة اختيارية من الأدمن تظهر في الخط الزمني (يراها العميل) |
| `internal_note` | text · nullable | ملاحظة داخلية للإدارة فقط — لا تظهر للعميل |
| `user_id` | FK → users | الأدمن اللي غيّر |
| `created_at` | timestamp | الوقت في الخط الزمني |

Indexes: `(order_id, created_at)`

### `payments`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `order_id` | FK → orders |  |
| `gateway` | string | paymob |
| `gateway_ref` | string | intention / order id |
| `transaction_id` | string | unique |
| `amount` | decimal(12,2) |  |
| `currency` | string | EGP |
| `status` | enum(pending, paid, failed, refunded) |  |
| `card_brand` | string |  |
| `card_last4` | string |  |
| `failure_code` | string | مثال: 51 |
| `failure_message` | string |  |
| `raw` | json |  |
| `hmac_verified` | boolean |  |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `order_id`, `transaction_id unique`

### `webhook_events`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `provider` | string | paymob |
| `event_id` | string | unique: يمنع التكرار |
| `payload` | json |  |
| `processed_at` | timestamp |  |
| `created_at` | timestamp |  |

Indexes: `(provider, event_id) unique`

## Customers

### `customers` — guard منفصل عن الأدمن · "عضو منذ" = created_at
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `name` | string |  |
| `mobile` | string | unique، صيغة مصرية |
| `email` | string | unique |
| `password` | string | 8 حروف ومنها رقم |
| `status` | enum(active, disabled) |  |
| `admin_notes` | text |  |
| `last_login_at` | timestamp |  |
| `remember_token` | string |  |
| `deleted_at` | timestamp | soft delete |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `mobile unique`, `email unique`, `status`

### `customer_addresses`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `customer_id` | FK → customers |  |
| `label` | enum(home, office, other) |  |
| `recipient_name` | string |  |
| `mobile` | string |  |
| `governorate_id` | FK → governorates |  |
| `city` | string |  |
| `street` | string |  |
| `floor_apartment` | string |  |
| `landmark` | string |  |
| `is_default` | boolean |  |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `customer_id`

### `wishlists`
| column | type | notes |
|---|---|---|
| `customer_id` | FK → customers |  |
| `product_id` | FK → products |  |
| `created_at` | timestamp |  |

Indexes: `PK(customer_id, product_id)`

### `customer_password_reset_tokens`
| column | type | notes |
|---|---|---|
| `email` | string | PK |
| `token` | string | مرة واحدة |
| `created_at` | timestamp | صلاحية 30 دقيقة |

## Leads

### `quote_requests` — كل ضغطة واتساب، حتى لو الرسالة متبعتتش
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `source` | enum(product, cart, instapay_order, boq, brand, service, project, article, order_help, general) |  |
| `sourceable_type` | string | الموديل المرتبط |
| `sourceable_id` | int |  |
| `product_variant_id` | FK → product_variants |  |
| `qty` | int |  |
| `cart_snapshot` | json | للسلة |
| `order_id` | FK → orders | لطلب InstaPay أو المساعدة |
| `customer_id` | FK → customers |  |
| `contact_name` | string | تلقائي لو العميل داخل أو من الطلب، وإلا الموظف بيكتبه بعد الشات |
| `contact_mobile` | string | نفس الفكرة |
| `page_url` | string |  |
| `locale` | enum(ar, en) |  |
| `ip` | string |  |
| `user_agent` | string |  |
| `session_id` | string |  |
| `status` | enum(new, contacted, converted, closed) |  |
| `admin_notes` | text |  |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `(status, created_at)`, `source`, `(sourceable_type, sourceable_id)`

### `consultation_requests`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `service_id` | FK → services |  |
| `name` | string |  |
| `mobile` | string |  |
| `project_type` | enum(apartment, villa, office, retail_fnb, hotel) |  |
| `area_range` | enum(lt150, 150_400, 400_1000, gt1000) |  |
| `notes` | text | اختياري |
| `locale` | enum(ar, en) |  |
| `ip` | string |  |
| `status` | enum(new, contacted, converted, closed) |  |
| `admin_notes` | text |  |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `(status, created_at)`, `service_id`

### `newsletter_subscribers`
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `email` | string | unique |
| `locale` | enum(ar, en) |  |
| `source` | enum(footer, blog) |  |
| `ip` | string |  |
| `unsubscribed_at` | timestamp |  |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

Indexes: `email unique`

## System

### `users` — الأدمن بس · دور واحد Admin عن طريق Spatie
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `name` | string |  |
| `email` | string | unique |
| `password` | string |  |
| `is_active` | boolean |  |
| `last_login_at` | timestamp |  |
| `remember_token` | string |  |
| `created_at` | timestamp |  |
| `updated_at` | timestamp |  |

### `media` — spatie/laravel-medialibrary (مختصر)
| column | type | notes |
|---|---|---|
| `id` | bigint PK |  |
| `model_type` | string |  |
| `model_id` | int |  |
| `collection_name` | string |  |
| `custom_properties` | json | caption / title مترجم |
| `order_column` | int |  |

### `roles · permissions · model_has_roles · …`
| column | type | notes |
|---|---|---|
| `—` | string | جداول Spatie Permission القياسية |

### `sessions · jobs · failed_jobs · cache`
| column | type | notes |
|---|---|---|
| `—` | string | جداول Laravel القياسية (sessions على database لتسجيل الخروج من الأجهزة التانية) |

## `page_sections` schemas (put in `config/page_sections.php`)

Each row = one section of one page. `content` holds the fields below (text fields translatable `{ar,en}`; `items[...] ×N` = fixed-length repeatable list; `image`/`video`/`poster`/`images` = media on the row). Admin form and validation are generated from this config.

| page.key | fields |
|---|---|
| `home.hero` | headline · subheadline · eyebrow · button1_label · button1_link · button2_label · button2_link · video · poster |
| `home.features` | items[icon, text] ×4 |
| `home.categories_head` | eyebrow · title · button_label |
| `home.featured_head` | eyebrow · title · tab_new_label · tab_best_label · tab_smart_label |
| `home.services_head` | eyebrow · title · button_label |
| `home.smart_banner` | eyebrow · title · text · button1_label · button1_link · button2_label · button2_link · image |
| `home.brands_head` | eyebrow · title · button_label |
| `about.hero` | eyebrow · title · text · image · video · button1_label · button1_link · button2_label · button2_link |
| `about.story` | eyebrow · title · text · images ×3 |
| `about.stats` | items[value, suffix, label] ×4 |
| `about.mission_vision` | mission_text · mission_image · vision_text · vision_image |
| `about.disciplines` | eyebrow · title · items[title, text, icon, image, link] ×3 |
| `about.why` | eyebrow · title · items[icon, title, text] ×6 |
| `about.partners_head` | eyebrow · title · button_label |
| `about.cta` | title · text · background_image |
| `services.head` | eyebrow · title · intro · button_label |
| `services.process` | eyebrow · title · items[title, text] ×4 |
| `services.faq_head` | eyebrow · title |
| `brands.head` | eyebrow · title · lede |
| `projects.current_hero` | eyebrow · title · lede · image · button_label |
| `projects.previous_head` | eyebrow · title · lede |
| `blog.head` | eyebrow · title · lede |
| `global.quote_band` | eyebrow · title · bullets ×4 · hours_text · button_label |
| `global.mega_promo` | image · title · button_label · button_link |
| `global.newsletter` | eyebrow · title |

## `settings` keys

| group | keys |
|---|---|
| `contact` | whatsapp_number · hotline · email · maps_url · showroom_label · showroom_address · working_hours · social_instagram · social_facebook · social_linkedin · social_youtube · util_strip_texts |
| `commerce` | instapay_address · vat_rate · vat_note · project_pricing_note · low_stock_threshold · instapay_hold_hours · order_prefix · product_trust_items · product_delivery_text · product_returns_text · currency |
| `whatsapp` | tpl_product · tpl_cart · tpl_instapay_order · tpl_boq · tpl_general · tpl_order_confirm_admin · tpl_order_update |
| `footer` | about_text · payments_line · copyright |
| `seo` | default_title · default_description · og_image |

Text settings are translatable `{ar,en}`. Seed defaults from the design copy.
