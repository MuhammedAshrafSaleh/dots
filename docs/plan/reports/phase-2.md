# Phase 2 Report — Database, models, seeders (2026-10-08)

All 48 ERD tables (60 incl. framework), their models, relations, casts, cache columns, observers and design-faithful seed data. Checklist all green.

- **T2.1** (`deab30b`) Catalog: 10 tables, per-locale generated STORED slug columns, FULLTEXT(ngram) search_text, media collections, translatable + enum casts, relations. Test suite moved to MySQL `dots_testing`.
- **T2.2** (`2ca0e02`) Content: 14 tables (services, projects, blog, pages, page_sections, faqs, settings); `config/page_sections.php` (25 schemas) + `PageSection` typed-field accessor/validation; cached `Settings` repository.
- **T2.3** (`20517a0`) Commerce/customers/leads: 21 tables; 12 backed enums; race-safe order number (prefix + id, AUTO_INCREMENT 10001); Customer auth model (soft deletes); order snapshots.
- **T2.4** (`2dc0bf6`) `UpdateProductCacheColumns` (min_price/max_compare_at/stock_status/search_text from variants) + observers invalidating tagged cache via a driver-safe `CacheTags` helper.
- **T2.5** (`e5264e4`) spatie/permission; `config/permissions.php` (56 permissions); `User` HasRoles + is_active/last_login_at; idempotent `RolesAndAdminSeeder` (admin from `config/dots.php`).
- **T2.6** (`dbefde5`) Design seeders (governorates, attributes, catalog tree/brands/products, content, commerce, 25 page_sections + settings) with local design images via Media Library; `Slug` helper; `LargeCatalogSeeder` (10k).
- **T2.7** (`585a112`) `DemoSeeder` (local/staging): customers, orders across every state, Paymob logs, coupons, leads for every source, consultations, newsletter.

**Review round 1 (2026-10-08):**
- **T2.6b** (`8cf089b`) page_sections + settings copy replaced with verbatim design AR/EN strings; placeholder keys documented (Decisions); README local-setup steps incl. `php artisan storage:link`.
- **T2.7b** (`f4b89f4`) all demo emails → `@example.com`.
- **T2.7c** (`0db5fdc`) DemoSeeder/LargeCatalogSeeder no-op in production; admin password required from `ADMIN_PASSWORD` in production (no default), seeding throws if empty; `ProductionSeedGuardsTest` added.
- **T2.6c** (`9632272`) all 7 `whatsapp.tpl_*` seeded as admin-editable AR/EN defaults with `{placeholder}` tokens; new `App\Services\WhatsApp` renders messages + wa.me links from the settings (never hardcoded); test proves editing a template changes the generated text.

**Verification:** `php artisan test` **85 passed / 327 assertions** (MySQL `dots_testing`) · `composer analyse` 0 errors (level 6) · `pint --test` clean · `migrate:fresh --seed` populates the design site (12 products, 65 categories, 103 media, 25 sections, 29 settings) and is idempotent · `LargeCatalogSeeder` 10k in ~6.5s. No secrets committed; `.env.testing` gitignored.

**Carried to later phases:** FULLTEXT ngram EXPLAIN/validation on the 10k seeder (Phase 6/9); customer password-reset broker (Phase 8).
