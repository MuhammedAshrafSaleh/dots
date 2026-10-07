# Phase 2 — Database, models, seeders

**Goal:** Every table from ERD.md with models, relations, casts and realistic seed data from the design.

Rules: read only this file + PROGRESS.md at session start; open refs only when a task needs them; one commit per task; STOP at the end of the phase.

## T2.1 — Catalog migrations + models
- categories, brands, products, product_variants, attributes, attribute_values, product_variant_values, product_specs, product_relations, category_featured_products.
- Translatable attributes, media collections (images with caption custom property, files with title/type), relations, scopes (published, active), indexes exactly as ERD.
- **Design/docs:** docs/ERD.md#catalog
- **Done when:** migrate:fresh works; model relation tests pass.

## T2.2 — Content migrations + models
- services, service_items, projects, project_product, project_service, post_categories, posts, tags, post_tag, post_product, pages, page_sections, faqs (polymorphic), settings.
- `config/page_sections.php` with the schemas from ERD.md; a PageSection accessor that returns typed fields with defaults.
- Settings repository with cache (get/set by group.key, translatable values).
- **Design/docs:** docs/ERD.md#content
- **Done when:** Tests: page section schema validation; settings get/set + cache bust.

## T2.3 — Commerce, customers, leads migrations + models
- governorates, delivery_methods, delivery_method_governorate, payment_methods, coupons, coupon_redemptions, carts, cart_items, orders, order_items, order_addresses, order_status_histories, payments, webhook_events, customers, customer_addresses, wishlists, customer_password_reset_tokens, quote_requests, consultation_requests, newsletter_subscribers.
- Enums as PHP backed enums. Order number generator `DT-` + sequence from 10001 (race-safe).
- **Design/docs:** docs/ERD.md#commerce--orders, #customers, #leads
- **Done when:** Tests: order number uniqueness under concurrency (DB lock), enum casts.

## T2.4 — Cache columns and observers
- Variant save/delete updates product `min_price`, `max_compare_at`, `stock_status` and `search_text` (names both locales + all SKUs + brand).
- Model observers that flush the right cache tags (categories, brands, facets, listings, sections, settings).
- **Design/docs:** PRD §5, §12
- **Done when:** Tests: changing a variant price/stock updates the product cache columns.

## T2.5 — Roles and admin user
- Permission list (one per admin area + action), role `Admin` with all permissions, seeded admin user from env (ADMIN_EMAIL/ADMIN_PASSWORD).
- **Design/docs:** PRD §11
- **Done when:** Seeder idempotent.

## T2.6 — Seeders from the design
- 27 governorates (ar/en). Attributes CCT/wattage/size/finish with values and swatches from data.jsx K_FACETS/K_TEMPS/K_FINISHES.
- Categories tree from MegaMenu.jsx DOTS_MEGA_DEFAULT; brands from K_BRANDS; products from K_PRODUCTS with variants; services K_SERVICES (+ FAQ from Services.jsx); projects K_PROJECTS + K_DONE (no before/after); posts K_ARTICLES + K_BLOG_CATS; content pages from Content.jsx KC_PAGES + terms/privacy placeholders; page_sections and settings defaults from the design copy; 3 payment methods; 4 example delivery methods (editable).
- Images: use EXACTLY the same photo the design shows for each record. design/website/src/ui_kits/website/data2.jsx maps them: kProdPh (products), kCatPh (categories), K_SUBCATS images, kSvcPh (services), project `image`/`gallery` numbers, article `image` numbers, and the kPh(n) calls inside each page (hero, about, banners, auth screens). kPh(n) = design/website/assets/images/ph{n}.jpg. Hero video = heroVid.mp4, logo = logo.png. Attach them through Media Library so the live site looks identical to the design.
- Brand logos: the design shows brand names as wordmarks (no logo files) — seed without logos and render the wordmark fallback exactly like BrandCard.jsx.
- A separate `LargeCatalogSeeder` (10k products) for EXPLAIN/perf only.
- **Design/docs:** design/website/src/ui_kits/website/data.jsx, data2.jsx, MegaMenu.jsx, Content.jsx
- **Done when:** `migrate:fresh --seed` gives a site whose content AND images match the design page by page.

## T2.7 — Demo data for the admin (DemoSeeder)
- Runs only when APP_ENV is local/staging or with `--class=DemoSeeder` (never automatically in production).
- Customers from Account.jsx (KA_USER + more), with addresses (KA_ADDR) and wishlists (KA_WISH_START).
- Orders covering every state from Orders.jsx / Account.jsx (KA_ORDERS, KO_ITEMS, KO_TRACK) and the admin kit samples (A_ORDERS): COD, card paid, InstaPay awaiting, cancelled + refunded, delivered; with items, addresses, status histories (with notes and times) and a Paymob payment log like AO_LOG. Mix of guest and customer orders.
- Coupon DOTS10 (10%) + 2 more, used on some orders.
- WhatsApp leads for every source, consultation requests, newsletter subscribers — use the admin kit samples (A_LEADS, A_CONSULTS) as the source.
- Dates spread over the last 60 days so the dashboard KPIs and charts look alive.
- Demo customer login printed by the seeder (e.g. ahmed.samir@gmail.com / a demo password) and the admin login from env.
- **Design/docs:** design/website/src/ui_kits/website/Account.jsx, Orders.jsx; design/admin/src/admin-kit.jsx (A_ORDERS, A_LEADS, A_CONSULTS, A_COUPONS)
- **Done when:** After `migrate:fresh --seed && db:seed --class=DemoSeeder` every admin screen and the account area show realistic data; nothing looks empty.

## Phase checklist
- [ ] All ERD tables exist with indexes
- [ ] Seeded content and images mirror the design page by page
- [ ] DemoSeeder fills admin + account screens
- [ ] Cache columns stay correct
- [ ] Tests green
