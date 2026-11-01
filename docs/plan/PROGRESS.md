# PROGRESS

## Current state _(read this first — a new session resumes from here)_
- **Branch:** `payments-hardening` (local only).
- **Current task:** PAY.10 — **DONE** (awaiting owner browser review).
- **Last commit:** `<pending>` — `PAY.10: re-reserve stock + re-consume coupon when an order is reinstated out of Cancelled`
- **Gate result:** 708 tests (706 pass, 2 skip) · Larastan 0 errors · Pint clean · build clean.
- **Context:** `bulk-import` merged to `main` and tagged `bulk-import-done`. `payments-hardening` is cut from that up-to-date `main`. Phase 10 follows after this branch is merged.
- **PAY.10 produced:** `UpdateOrderStatus` restored stock on cancel but did nothing on the reverse boundary, so reinstating a cancelled order fulfilled against stock already handed back. Fix (owner-approved: allow+warn+record, clamped at zero; re-consume coupon): `handle()` now opens its transaction with **one** `Order::lockForUpdate()` re-read of the authoritative status and computes **both** `$cancelling` and `$reinstating` from it (there was no prior lock in this action — only the sweeper had one; this is a single mechanism for both directions, also hardening the cancel path). Reinstating runs `reserveStock()` (locks each variant, decrements `min(available, needed)`, never negative) and `ConsumeOrderCoupon` (new, exact reverse of `ReleaseOrderCoupon`). `reserved_until` stays null. Short lines surface as a `warning` toast **and** a persisted internal note on the history row (no auto customer message). Returns `list<shortage>`; `Detail.php` raises the toast.
- **Open questions from payments.md (still open, non-blocking):**
  1. Should auto-cancelled orders be visually distinct from admin-cancelled ones in the admin orders list?
  2. PAY.8 real-transaction test: run it yourself following the doc, or also include a sandbox dry-run walkthrough?
- **How to verify (owner clicks — PAY.10):** Admin → Orders → open a **Cancelled** order with items in stock → set status to **Confirmed** → Save: the order's variant `stock_qty` must drop by the ordered qty, and (if it carried a coupon) the coupon counts again. Then take a Cancelled order whose variant stock is **less than** the ordered qty, reinstate it: Save still succeeds, an orange **warning** toast names the short product(s), stock floors at 0 (never negative), and the order's history shows an **Internal** note "Reinstated with insufficient stock — call the customer…". (Earlier PAY.9/PAY.14 owner-reviews may still be pending — see their lines in payments.md.)
- **Next session:** Owner approves all PAY tasks → say "approved, push payments-hardening" to push and merge.

**Git:** branch `payments-hardening` (local, unpushed) · last commit `<pending PAY.10>` · last tag `bulk-import-done` · push state: **not pushed**

Current phase: **Payments hardening on branch `payments-hardening`.** Execution order: PAY.3 → PAY.2 → PAY.1 → PAY.4 → PAY.5 → PAY.6 → PAY.7 → PAY.8. Phase 10 follows after this branch is merged.

---

## Phase 0 — Review (no code)  (`phase-00-review.md`)
- [x] T0.1 Read and cross-check
- [x] T0.2 Write the review report

## Phase 1 — Foundation  (`phase-01-foundation.md`)
- [x] T1.1 Create the project
- [x] T1.2 Locales and routing
- [x] T1.3 Front-end build and design CSS
- [x] T1.4 Base layouts and Blade components
- [x] T1.5 Auth guards
- [x] T1.6 Quality tooling

## Phase 2 — Database, models, seeders  (`phase-02-database.md`)
- [x] T2.1 Catalog migrations + models
- [x] T2.2 Content migrations + models
- [x] T2.3 Commerce, customers, leads migrations + models
- [x] T2.4 Cache columns and observers
- [x] T2.5 Roles and admin user
- [x] T2.6 Seeders from the design
- [x] T2.7 Demo data for the admin (DemoSeeder)

## Phase 3 — Admin: shell, shared widgets, catalog  (`phase-03-admin-catalog.md`)
- [x] T3.1 Admin auth and shell
- [x] T3.2 Shared admin widgets (Livewire/Blade)
- [x] T3.3 Attributes
- [x] T3.4 Brands
- [x] T3.5 Categories
- [x] T3.6 Products list
- [x] T3.7 Product edit

## Phase 4 — Admin: content and settings  (`phase-04-admin-content.md`)
- [x] T4.1 Page content editor
- [x] T4.2 Services
- [x] T4.3 Projects
- [x] T4.4 Blog
- [x] T4.5 Content pages
- [x] T4.6 Settings and users
- [x] T4.7 Rich text editor: TipTap everywhere (carried from Phase 3)
- [x] T4.8 Product Files tab: named slots + titled extras (carried from Phase 3)

## Phase 5 — Public site: layout and content pages  (`phase-05-public-content.md`)
- [x] T5.1 Global layout
- [x] T5.2 WhatsApp click logging
- [x] T5.3 Home
- [x] T5.4 About
- [x] T5.5 Services and service detail
- [x] T5.6 Projects
- [x] T5.7 Blog and article
- [x] T5.8 Brands and brand detail
- [x] T5.9 Content pages
- [x] T5.10 SEO basics

## Phase 6 — Public site: catalog  (`phase-06-catalog-front.md`)
- [x] T6.1 Category page
- [x] T6.2 Products listing (Livewire)
- [x] T6.3 Product card
- [x] T6.4 Product detail
- [x] T6.5 Search

## Phase 7 — Cart, checkout, payments, orders  (`phase-07-commerce.md`)
- [x] T7.1 Cart
- [x] T7.2 Checkout
- [x] T7.3 Confirmation, payment failed, track order
- [x] T7.4 InstaPay via WhatsApp
- [x] T7.5 Paymob driver
- [x] T7.6 Admin: orders
- [x] T7.7 Admin: coupons, delivery, payments, leads, consultations, newsletter, dashboard
- [x] T7.8 Notifications

## Phase 8 — Customer accounts  (`phase-08-accounts.md`)
- [x] T8.1 Auth screens
- [x] T8.2 Linking and merging
- [x] T8.3 Account area
- [x] T8.4 Wiring
- [x] T8.5 Admin: customers

## Phase 9 — Performance and operations  (`phase-09-performance-ops.md`)
- [x] T9.1 Query and cache review
      → report below ("T9.1 — Query and cache review report")
- [x] T9.2 Images
- [x] T9.3 Backups and scheduler
- [x] T9.4 Security and errors

## Phase 10 — QA and deployment  (`phase-10-qa-deploy.md`)
- [ ] T10.1 Content coverage report
- [ ] T10.2 Full test pass and manual QA
- [ ] T10.3 Deployment guide

## Phase 11 — Deferred (start only when unblocked)  (`phase-11-deferred.md`)
- [ ] T11.1 Products Excel import/export
- [ ] T11.2 Google Drive backups

Legend: [ ] todo · [x] done

---

## T9.1 — Query and cache review report

**Dataset:** `LargeCatalogSeeder` on `dots_testing` → 10,012 active products · 10,020 variants · 20,034 variant-values · 65 categories · 8 brands. MySQL 9.6 (tree-format `EXPLAIN`). Dev `dots` DB was left untouched (DB-safety rule); all EXPLAIN work ran on the throwaway test DB. Timings are best-of-3 of the raw SQL (no eager-load / Blade overhead); treat as relative, not absolute.

**Findings — every query already uses an index; no full table scan on `products`.** The one recurring cost was a **filesort over the full active set (~5006 est. rows)** on the uncached listing sorts, which run on every listing page view that has no/weak filters. Filtered variants (category / brand / price) were already fast because the FK / `min_price` indexes shrink the set before the sort.

**Change made (ERD index addition — flagged for owner review):** migration `2026_10_20_000001_add_listing_sort_indexes_to_products_table` adds two composite indexes (raw `ALTER`, DESC columns, MySQL-only like the FULLTEXT one), and `docs/ERD.md` products Indexes line updated:
- `products_recommended_sort_index (status, sort_weight DESC, is_best_seller DESC, id)` — the "recommended" default sort.
- `products_published_sort_index (status, published_at DESC, id DESC)` — the "newest" sort.

**Before → after (best-of-3 ms):**

| Query | Before | After | Note |
|---|---|---|---|
| Listing, recommended sort, LIMIT 12 | 15.0 | **0.2** | filesort eliminated → index lookup |
| Listing, newest sort, LIMIT 12 | 10.6 | **0.2** | filesort eliminated |
| Listing, inStock + recommended sort | 9.8 | **0.2** | sort served by new index, stock filtered on top |
| Listing, category scope + sort | 0.4 | 0.4 | already fast (FK index) |
| Listing, brand filter + sort | 1.7 | 1.7 | already fast (FK index) |
| Listing, price range + sort | 0.7 | 0.7 | already fast (`min_price` range scan) |
| FACET brand counts (group by brand_id) | 6.7 | 6.7 | aggregate, covered by `(status,category_id)` |
| FACET category counts (group by category_id) | 1.5 | 1.5 | covering index on `(status,category_id)` |
| FACET attr counts (pvv⋈pv⋈products) | 18.4 | 18.4 | see rejected index below |
| SEARCH fulltext MATCH…AGAINST + sort | 32.5 | 32.5 | FULLTEXT index used; `Perf*` matches all 10k (worst case) |
| CATEGORY page count in scope | 1.7 | 1.7 | FK index |
| BRAND page products + sort LIMIT 8 | 0.5 | 0.5 | already fast |
| HOME is_new/best/smart + sort LIMIT 4 | ~8.6 | ~8.6 | worst case: seeder flags none, so it scans the whole sort_weight index finding 0; in prod a handful are flagged near the top → fast. Not worth a per-flag index. |

**Rejected index:** `product_variants (product_id, is_active)` for the attr-facet join — EXPLAIN switched the `pv` table scan to a covering index scan but timing was unchanged (18.4 → 19.1 ms), because the facet inherently needs every active product's variants. Not added (pure write-cost). Attr facets are cached 120 s anyway.

**Cache tags & invalidation:** tags are centralised in `App\Support\CacheTags` (categories · brands · facets · listings · sections · settings) with a driver-safe tagged `remember()` / `flush()` (redis in prod, DB fallback flushes whole store). Facets cached 120 s keyed by locale+filter-hash; category tree / per-category children/total/brands cached 3600 s; invalidated by model observers. No invalidation gaps found for this task's scope.

**N+1:** `Model::shouldBeStrict()` is on outside production (`AppServiceProvider`), so any lazy load throws in dev/tests. Listing / home / category / brand queries all eager-load (`media`, `brand`, `variants.swatchAttributeValues`). Full suite passes under strict mode — no N+1.

**Gate:** 565 tests (563 passed / 2 skipped) · PHPStan 0 · Pint clean · build OK.

---

## Decisions log
- **2026-10-27 — Coupon rollback on cancel = soft-release, not delete (PAY.1, Gap B):** Owner decision. When an order is cancelled (auto-sweep in `ReleaseExpiredReservations` or admin cancel in `UpdateOrderStatus`), its `CouponRedemption` row(s) are **soft-released** — a new nullable `released_at` timestamp is stamped (not hard-deleted, not Laravel `SoftDeletes`/`deleted_at`) — so the history of a coupon used on a later-cancelled order is preserved and the row stays visible/labelled. **Two gates consume a coupon and both must be handled:** (1) the **usage-limit** gate reads the `coupon.used_count` *counter* (`Coupons::resolve` + `Coupon::scopeValid`), so we `decrement('used_count')` by the number of rows actually released (floored at 0); (2) the **once-per-customer** gate reads redemption *rows* via `exists()` (`Coupons.php`), so it gains `->whereNull('released_at')` to free the customer after a cancel. Idempotent: a re-run finds no active rows and no-ops. **Guest observation (behaviour unchanged by PAY.1):** the once-per-customer gate is wrapped in `$customerId !== null`, so a guest order (null `customer_id`) is exempt from the rule today; the `whereNull('released_at')` clause lives inside that block and does not alter guest behaviour either way. ERD updated (`coupon_redemptions.released_at`).
- **2026-10-20 — Bulk import pulled forward from Phase 11 (T11.1):** Owner decision. Built now on branch `bulk-import` (off main after `phase-9-done`) ahead of the client meeting, as 3 tasks BI.1–BI.3 (`docs/plan/bulk-import.md`), spec in `docs/IMPORT_SPEC.md`. After BI.3 the next step is **Phase 10** (Phase 9 does not resume). Release works like a phase: on "approved, push bulk-import" → push, `merge --no-ff` into main, tag `bulk-import-done`, push. T11.1 in the Phase 11 file is now a pointer. Key spec decisions:
  - **Columns mapped by the hidden row-2 system key, never by position** — reordered/renamed row-1 headers don't break import.
  - **Reference data is never created by import** — unknown brand/category/attribute value = row error ("add in admin, re-download the template"); import never writes to brands/categories/attributes/attribute_values, and never deletes products.
  - **Media problems are warnings, not row errors** — a product still imports without a bad/missing file; a NEW product ending with zero valid images is forced to `draft`. DATA problems skip the row.
  - **`SKU-manual.pdf` → the `guide` (installation guide) file slot** (the 4 T4.8 slots are `datasheet/ies/ldt/guide`, no "manual" slot).
  - **Reuse** `App\Actions\UpdateProductCacheColumns` + `App\Support\CacheTags` for post-apply recompute/flush (imported price shows on the public page immediately), `App\Support\Slug` for slugs, `App\Support\Html` purifier for `overview_*`. New permissions `products.import` / `products.export`.
- **2026-10-20 — Newsletter kept and wired (post-T9.4 gap fix):** Newsletter kept and wired — free (own DB, no auto emails per Q8 decision); email list is a marketing asset. `newsletter.subscribe` changed from GET placeholder to POST `NewsletterController::subscribe` with `throttle:10,1`. Footer form updated to POST + @csrf. Duplicates handled gracefully via `firstOrCreate`/`wasRecentlyCreated` — no 500 or validation error for re-subscriptions.
- **2026-10-20 — No strict script-CSP (T9.4):** Technical choice. `SecurityHeaders` ships a Content-Security-Policy limited to `frame-ancestors 'self'` (anti-clickjacking) and leaves script/style sources unrestricted. A strict `script-src` would require `'unsafe-inline'` + `'unsafe-eval'` to keep Livewire and Alpine (and the inline no-FOUC `<script>` in `app.blade.php`) working, which defeats the purpose. Clickjacking, MIME-sniffing, referrer leakage and feature-policy are covered by the other headers; HSTS is sent over HTTPS only. Revisit if the front-end moves off inline Alpine.
- **2026-10-20 — Muted text color darkened (T9.2 Lighthouse fix):** Design deviation. `--ink-500` was `#75756E` (WCAG 4.74:1 on white but only 4.33:1 on the site's soft-gray `--ink-50` = `#F7F7F5` surface used by `.d-phead` and `.d-footer`). Darkened to `#6F6F68` which gives 4.72:1 on `#F7F7F5` (≥ AA 4.5:1). The change is minimal — 6 steps darker in lightness. Affects all uses of `--fg-3` / `--ink-500` across the site (phead counts, footer bottom strip, form placeholders, sku, muted metadata). Previous value is documented here for reference.
- **2026-10-20 — Image driver = Imagick (T9.2):** Technical choice. Responsive WebP conversions on the full DemoSeeder exhausted PHP's 128 MB `memory_limit` under the GD driver (`imagewebp` on large truecolor bitmaps). Switched media-library's `image_driver` to **imagick** via `IMAGE_DRIVER=imagick` (`.env`, `.env.example`, `phpunit.xml`) — Imagick holds pixel buffers in its own C allocations, outside `memory_limit`, and gives better WebP. Requires the `imagick` PHP extension in every environment (prod must have it; falls back to `gd` if the env var is unset). Also: tests route conversion jobs to a non-running connection by default so the suite doesn't re-encode every seeded image (TestCase sets `media-library.queue_connection_name => database`; opt back into `sync` per-test when a real conversion file is needed).
- **2026-10-20 — Reorder dropped (T8.3 manual review):** Owner decision. The Reorder feature (add past order's items back to cart) was removed entirely — too complex to do well (variant availability, price staleness, partial availability), and not required by the PRD. Removed from `OrderDetail`, lang files, and tests. The phase-8.md plan document mentioned it; the report notes it as dropped.
- **2026-10-20 — Max saved addresses = 4 (T8.3 manual review):** Owner decision. `CustomerAddress::MAX_PER_CUSTOMER = 4`. Enforced server-side in `Addresses::create/save` and silently in `CheckoutForm::persistAddress`. View hides the add button and shows a hint at the limit.
- **2026-10-07 — Versions:** do not assume from memory. At install time (Phase 1) use the latest stable majors `composer`/`npm` resolve for Laravel, Livewire, Pest and Vite, provided all required Spatie packages (translatable, medialibrary, permission, backup, sitemap) support them. Record the exact resolved versions here once installed. Local toolchain: PHP 8.4.23, Composer 2.10, Node 22, npm 10.
  - **Resolved at install (T1.1, 2026-10-07):** `laravel/framework` v13.35.0 (latest stable; all Spatie packages support L13), `livewire/livewire` v4.4.7, `spatie/laravel-translatable` 6.14.1, `spatie/laravel-medialibrary` 11.23.9, `spatie/laravel-permission` 8.3.0, `spatie/laravel-backup` 10.3.3, `spatie/laravel-sitemap` 8.2.0, `mews/purifier` 3.4.4 (HTML sanitizer per REVIEW), `pestphp/pest` v4.7.8, `vite` ^8, `@vitejs` via `laravel-vite-plugin` ^3.1. Composer `php` constraint `^8.3`. Local `.env`: MySQL `dots` db, `QUEUE_CONNECTION=sync`, `CACHE_STORE=database` (no local Redis daemon running); `.env.example` keeps `redis` for prod. Tests run on sqlite `:memory:` (phpunit.xml). NOTE: Laravel 13's scaffolding ships a Tailwind `@tailwindcss/vite` entry + default CLAUDE.md/AGENTS.md — restored the project CLAUDE.md, removed AGENTS.md; Tailwind to be stripped in T1.3 (project is no-Tailwind).
- **2026-10-07 — DB target (Q9):** production MySQL **8.0**. Migrations/SQL must stay 8.0-compatible (no 9.x-only syntax). Local MySQL 9.6 is fine.
- **2026-10-07 — Paymob (Q1):** Intention / Unified Checkout API (not legacy Accept). Credentials from `.env` only; card method hidden when unconfigured.
- **2026-10-07 — Order number (Q2):** `number` = `settings.commerce.order_prefix` (default `DT-`) + `orders.id`; table AUTO_INCREMENT starts at 10001; generated & stored once at creation in the same transaction; unique index; also the tracking number. Prefix change affects new orders only. Applied to PRD + ERD.
- **2026-10-07 — Slugs (Q3/Q4):** translatable `slug` JSON + generated STORED columns `slug_ar`/`slug_en` with UNIQUE indexes; route binding against them + app-level validation. Arabic-script slugs kept in AR URLs via a Unicode-preserving slugger (`-2`/`-3` on collision); editable in admin. Applied to ERD.
- **2026-10-07 — page_sections links (Q5):** added `global.mega_promo.button_link` and `about.hero.button1_link`/`button2_link`. Applied to ERD + CONTENT_MAP.
- **2026-10-07 — Rich text (Q6):** TipTap in an Alpine wrapper (`wire:ignore` with Livewire); allowed blocks only: h2, h3, p, bold, italic, link, ul/ol, blockquote, image+caption, callout. Sanitize on save with `mews/purifier` using a per-field allowlist.
- **2026-10-07 — Services process (Q7):** the 4 "how we work" steps are one global `page_sections` row shared by all services; only `duration_text` is per service.
- **2026-10-07 — No SMS / no automatic customer messages (Q8):** no SMS and no automatic customer email (no order-confirmation email). Password reset email only (customer-initiated); admin new-order email allowed. BOQ button = logged WhatsApp link (`quote_requests.source = boq`), no upload control anywhere. Copy strings for Phase 1 lang files + Phase 5/7 views:
  - Track lede — AR: "أدخل رقم الطلب الظاهر في صفحة تأكيد الطلب لمتابعة حالته." · EN: "Enter the order number shown on your order confirmation page to follow its status."
  - InstaPay option — AR: "التحويل وإرسال الإيصال عبر واتساب" · EN: "Transfer and send the receipt via WhatsApp".
  - Confirmation page — AR: "احتفظ برقم الطلب لمتابعة حالته" · EN: "Keep your order number to track your order". Remove any line promising an SMS or email.
- **2026-10-07 — Account linking (Q10):** never merge accounts. Link a guest order only when BOTH mobile and email match the SAME customer; otherwise leave it a guest order silently (visible in admin). Reusing another account's mobile/email at registration = normal unique validation error.
- **2026-10-07 — VAT:** prices entered VAT-inclusive. Round each line total to 2 dp; order total = Σ line totals − coupon discount + delivery fee. VAT is display-only = total × 14 / 114, rounded to 2 dp. Snapshot all of it on the order.
- **2026-10-07 — Queue:** `sync` locally; database/redis in production (`QUEUE_CONNECTION` switchable in `.env`).
- **2026-10-07 — Search (decision to confirm in Phase 2/6):** FULLTEXT on `products.search_text` using the InnoDB **ngram** parser (handles short Arabic/SKU tokens that the default parser drops), with a `LIKE` fallback for exact SKU fragments. Validate on the ≥10k-product seeder with `EXPLAIN`; revise here if ngram underperforms.
- **2026-10-07 — Front-end (T1.3):** Removed the Laravel-default Tailwind (`@tailwindcss/vite`, `tailwindcss`) — project is no-Tailwind. Two Vite entries: site (`resources/css/site.css` + `resources/js/app.js`) and admin (`resources/css/admin.css` + `resources/js/admin.js`). Design CSS ported verbatim to `resources/css/design-site.css` / `design-admin.css`, each wrapped by its entry which first `@import`s `resources/css/fonts.css`. Fonts self-hosted via `@fontsource` (sora, manrope, ibm-plex-sans-arabic [Arabic+Latin], ibm-plex-mono). Alpine 3 started in both JS entries. Icons: `mallardduck/blade-lucide-icons` (set prefix `lucide`); our own `resources/views/components/icon.blade.php` owns `<x-icon name=".."/>` (disabled the package's default `icon` component via `config/blade-icons.php` `components.default=''`, `disabled=true`) and reproduces Icon.jsx (1.5 stroke, aliases, RTL-flip directional set). Styleguide at `/{locale}/_styleguide` (non-production only). Fixed: untracked `bootstrap/cache/*.php` (added `bootstrap/cache/.gitignore`).
- **2026-10-07 — Tooling (T1.6):** `larastan/larastan` ^3.13 at **level 6** (`phpstan.neon`, paths app/config/database/routes). One scoped exclude: `app/Models/Concerns/HasTranslatableSlug.php` (trait has no consumer until Phase 2 — remove the exclude then). Pint `laravel` preset (`pint.json`). `Model::shouldBeStrict(! production)` in `AppServiceProvider::boot` (lazy-loading/ missing-attribute/ discarded-attribute guards). Composer scripts: `test`, `lint` (pint), `analyse` (phpstan). `config/auth.php` customers provider declared as the string `'App\Models\Customer'` (not `::class`) so analyse stays clean before the model exists.
- **2026-10-08 — Test DB (owner):** Phase 2 needs MySQL-8-only features (FULLTEXT ngram, JSON→generated-STORED slug columns, race-safe sequences), so the Pest suite runs against a **dedicated MySQL database `dots_testing`** from Phase 2 on — NOT SQLite (supersedes the Phase 1 sqlite decision), and NEVER the dev `dots` DB (RefreshDatabase would wipe seeded data). Configured in `phpunit.xml` (explicit mysql/dots_testing env — enough on its own to connect) and a local `.env.testing` (gitignored because it carries `APP_KEY`; copy from `.env` and set `DB_DATABASE=dots_testing` if missing). Local MySQL is 9.6; prod target 8.0 — keep migrations 8.0-compatible. Create the DB with `mysql -u root -e "CREATE DATABASE IF NOT EXISTS dots_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"`.
- **2026-10-08 — Seed copy & dev DB (T2.6, verbatim in T2.6b):** all records, attributes, media **and** `page_sections`/`settings` marketing copy are **verbatim** from the design (data.jsx, data2.jsx, MegaMenu.jsx, Home/About/Services/Brands/Projects/Blog.jsx, Shared.jsx KQuoteBand, Footer.jsx, Navbar.jsx, OrderSummary.jsx, Products.jsx, Content.jsx). Images attach from local `design/website/assets/images/ph*.jpg` via Media Library (`preservingOriginal`); `php artisan storage:link` is required to serve them (in README setup). Keys the **design has no copy for — placeholders:** `global.newsletter.eyebrow` (footer form has only an input placeholder, used as the title); `seo.default_title` / `seo.default_description`; and operational config with no design value — `contact.whatsapp_number`, `contact.hotline` (design literally shows "19XXX"), `contact.email`, `contact.maps_url`, `contact.social_*`, `commerce.instapay_address`. The `whatsapp.tpl_*` templates (all 7 ERD keys) are seeded as **admin-editable defaults** (the design builds these messages in code) and are rendered by `App\Services\WhatsApp` via `{placeholder}` substitution — not placeholders in the "no copy" sense. The suite proves all seeders against `dots_testing` (disk faked); populating the **dev `dots`** DB is owner-run — `php artisan migrate:fresh --seed` (wipes dev data, expected) — the harness correctly blocks me from refreshing it. `LargeCatalogSeeder` (10k) is manual-only: `php artisan db:seed --class=LargeCatalogSeeder` (~6.5s locally).
- **2026-10-08 — Admin shell & auth (T3.1):**
  - **Livewire components are class-based** (`php artisan make:livewire --class`) under `app/Livewire/{Admin,…}` with views in `resources/views/livewire/…`, per CLAUDE.md — NOT Livewire 4's default single-file (`⚡`-prefixed) components.
  - **Admin area is not locale-prefixed.** The admin locale lives in the session (`admin_locale`), applied by `SetAdminLocale`; the language switch is `GET /admin/lang/{locale}`. (The public site keeps its `{locale}` prefix.)
  - **Guard vs permission guard.** Admins authenticate on the `admin` guard; `AdminAuth` middleware requires an active admin and calls `Auth::shouldUse('admin')` so the Gate/`can:` resolve the admin user. Because `shouldUse` also rewrites `config('auth.defaults.guard')`, spatie would otherwise look permissions up under `admin` (they are seeded under `web`). Fixed by pinning `User::$guard_name = 'web'`. `AdminAuth` is prepended before `Illuminate\Auth\Middleware\Authorize` in the middleware priority list so it runs before `can:`.
  - **Every admin route carries a named `can:` permission** (e.g. dashboard → `can:dashboard.view`). Spatie `permission`/`role` middleware aliases registered for later use.
  - **Sidebar/topbar are data-driven** from `config/admin_nav.php` (mirrors ASidebar A_NAV). An item links only when its route exists AND the admin holds its view permission; otherwise it renders a disabled "soon" placeholder, so links light up as later tasks build the screens.
  - **Admin password reset** uses the default `users` broker (shares the admin's `users` provider) with a custom `AdminResetPasswordNotification` linking to `admin.password.reset`. Forgot-password never reveals whether an email is registered.
- **2026-10-09 — Shared widgets & Livewire/Alpine (T3.2):**
  - **One Alpine, from Livewire.** Livewire 4 auto-injects its assets and starts its own Alpine, so `resources/js/admin.js` must NOT import/start a second Alpine (that threw "multiple instances"). It now registers the admin helpers via `alpine:init`/`livewire:init` against `window.Alpine`/`window.Livewire`: a `toasts` store (fed by `$this->dispatch('toast', …)`) and a `sortable` Alpine data factory (HTML5 drag → emits the new key order to a Livewire method). `resources/js/app.js` (public) still bundles its own Alpine for now (revisit if the public site adopts Livewire in Phase 6).
  - **Widget architecture:** presentational Blade components under `resources/views/components/admin/` bind to the parent Livewire component via `wire:model`/method names passed as props (so state stays server-authoritative); stateful search is a real Livewire widget (`ProductPicker`, `#[Modelable]`). Conditional attributes via `@if … @endif` inside an `<x-…>` tag break the Blade component compiler — always-render the attribute or branch in plain markup instead.
  - **Rich text (updates Q6):** the editor is an Alpine `contenteditable` wrapper with the design toolbar (`execCommand`), synced to Livewire via `$wire.entangle`. Full TipTap is deferred to the Phase 4 content editors; the security guarantee is server-side: `App\Support\Html::clean()` runs every rich field through the `dots_rich` HTMLPurifier profile (h2/h3/p/strong/em/a/ul/ol/li/blockquote/figure+figcaption/img/div.d-callout only) on save.
  - **phpstan:** `InteractsWithAdminForms` + `WithAdminTable` are temporarily in `excludePaths` (larastan `trait.unused` until a screen consumes them in T3.3/T3.6 — remove then; both are covered by the T3.2 widget-harness test).
- **2026-10-13 — Global layout (T5.1):**
  - **Site components** live under `resources/views/components/site/` (`header`, `mega-menu`, `footer`, `search-bar`, `lang-switch`, `quote-band`, `motion`), wired into `components/layouts/app.blade.php`. Data comes from the DB via `App\Services\Navigation` (category mega tree + footer categories/pages, queried live — caching is a Phase 9 concern) and the `Settings` service (contact/footer/socials) + `global.*` `page_sections` (mega_promo, newsletter, quote_band). Only nav/UI labels are in `lang/{ar,en}/ui.php`.
  - **Responsive `compact` mode.** The design keys mobile styling off a `.d-compact` ancestor class (not media queries). A blocking inline `<head>` script sets `.d-js` + `.d-compact` (≤860px) + `.d-preload` (first visit of a session, motion allowed) on `<html>` before paint (no FOUC); `resources/js/site/motion.js` keeps `.d-compact` in sync on resize. The header renders BOTH a full and a compact bar; `site.css` shows one per `.d-compact` state. No-JS guards in `site.css` reveal `.d-rv` and hide `.d-pre` when `.d-js` is absent.
  - **Motion** (`resources/js/site/motion.js`, port of Motion.jsx): first-visit preloader, page-transition curtain (intercepts same-origin left-clicks, ~480ms sweep, opt out with `data-no-curtain`), custom cursor (fine pointer), scroll reveal (IntersectionObserver). All disabled under `prefers-reduced-motion`.
  - **Language switch** = two `<a>` links (`Locale::switchUrl`), styled for anchors in `site.css` (design CSS only styled `button`).
  - **Placeholder routes.** `web.php` registers named routes the layout links to (`products`, `brands`, `services`, `projects.current`, `projects.previous`, `blog`, `about`, `track`, `search`, `category` `/c/{category}`, `page` `/p/{page}`, `newsletter.subscribe`) all rendering `site.coming-soon`. Each later Phase 5/6 task replaces the matching route body (keep the names). WhatsApp quote links use `WhatsApp::link('general')` for now; T5.2 swaps them for the logged `<x-whatsapp-button>`.
- **2026-10-13 — WhatsApp logging (T5.2):**
  - **No public `wa.me` links anywhere.** Every WhatsApp CTA in a public Blade view must use `<x-whatsapp-button source="...">` (or a plain `<a>` whose `href` is `URL::signedRoute('wa.redirect', …)`). The signed route logs a `quote_requests` row then redirects. `grep -r "wa\.me"` across app/ config/ database/ resources/ routes/ must only hit `app/Http/Controllers/WhatsAppController.php` and `app/Services/WhatsApp.php`. Admin views that build `wa.me` links for staff use (e.g. order update in T7.6) are exempt.
  - **Number normalisation.** `WhatsApp::normalizeNumber(string $raw)` strips non-digits and converts a leading-`0` local Egyptian number to international format (e.g. `01500060828` → `201500060828`). Both `WhatsApp::link()` and `WhatsAppController::redirect()` pass the raw setting through this helper. The setting `contact.whatsapp_number` should store the number in international format (`201xxxxxxxxx`) — the normaliser is a safety net, not the authoritative source.
  - **Client action pending:** set `contact.whatsapp_number` to the real number in international format via admin Settings → Contact.
- **2026-10-15 — Browser tests paused (owner decision):** Browser tests (`tests/Browser`) are excluded from all per-task and phase-end gates. The default test run (`composer test` / `php artisan test`) runs Unit + Feature only (via `phpunit.xml`). Browser tests live in `phpunit.browser.xml` and run only via `composer test:browser`, explicitly when the owner asks. New behavior is covered by Feature tests instead of new browser tests. Per-task gate is now `composer gates` = `composer test` + `composer analyse` + `vendor/bin/pint --test` + `npm run build`. **Re-run `composer test:browser` before Phase 7, before Phase 8, and before deploy.**
- **2026-10-20 — Register confirm-password (T8.1 fix F2):** `KARegister` in `Account.jsx` has no `password_confirmation` field. Added anyway (standard practice for registration forms — without it a typo in the password locks the user out immediately). Decision: always add confirm-password on any form that sets a password for the first time.
- **2026-10-20 — Email verification (T8.1 F4 — decision only, not implemented):** Current PRD says register logs the user in immediately with no email verification. Adding `MustVerifyEmail` to `Customer` would require: (1) verification routes + `CustomerVerifyEmailNotification`; (2) blocking un-verified customers from placing orders or accessing account (via `verified` middleware or explicit checks); (3) impact on `LinkGuestOrders` — guest orders linked at registration would be attached before email is verified, which may be undesirable; (4) impact on `SaveOrderAccount` (creates an account from a completed order's email — the account would need to be considered "verified by purchase" or trigger a verify email); (5) mobile-only login — a customer who registered via `SaveOrderAccount` on a mobile-only order has no password and cannot reset via email if they never verified. Recommendation: **do not add email verification now.** The checkout flow and mobile-only path make it complex, and the PRD explicitly deferred it. Re-evaluate in Phase 10 QA or after mobile-number verification (WhatsApp OTP) is available.
- **2026-10-15 — Redis cache serialization (T6.2 fix):** Laravel 13 defaults `config/cache.php` `serializable_classes => false`, which makes `RedisStore` unserialize with `allowed_classes => false` — every cached Eloquent model/collection comes back as `__PHP_Incomplete_Class` (500 on `/products`). Tests never caught it because the test env uses the `array` cache store (no serialization). This app intentionally caches its own Eloquent collections (category tree, brand/attribute facets) in a private Redis store, so set `serializable_classes => env('CACHE_SERIALIZABLE_CLASSES', true)`. Requirement: production Redis must stay non-public (the setting is only safe when the cache store is not attacker-writable).
