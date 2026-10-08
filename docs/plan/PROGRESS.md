# PROGRESS

**Git:** branch `phase-2` · last local commit `deab30b` (T2.1; T2.2 committing) · last pushed `2effb22` (main, Phase 1) · last tag `phase-1-done` · push state: phase-2 NOT pushed _(updated after every commit)_

Current phase: **2 — Database, models, seeders** (in progress)

### Phase 2 task log
- **T2.1** (`deab30b`) Catalog schema + models: 10 tables with per-locale generated slug columns, FULLTEXT ngram `search_text`, media collections, translatable + enum casts, relations. Suite moved to MySQL `dots_testing`. 34 tests green · pint · phpstan L6 clean.
- **T2.2** (hash recorded with next commit) Content schema + models: services/service_items, projects (+ pivots), post_categories/posts/tags (+ pivots), pages, page_sections, faqs (polymorphic), settings — 14 tables. `config/page_sections.php` (25 section schemas) + `PageSection` typed-field accessor & `validateContent`. Cached `Settings` repo (get/set/translated, per-key bust). Added CLAUDE.md "run straight through the phase" rule. 49 tests · pint · phpstan clean.

Legend: [ ] todo · [x] done


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
- [ ] T2.3 Commerce, customers, leads migrations + models
- [ ] T2.4 Cache columns and observers
- [ ] T2.5 Roles and admin user
- [ ] T2.6 Seeders from the design
- [ ] T2.7 Demo data for the admin (DemoSeeder)

## Phase 3 — Admin: shell, shared widgets, catalog  (`phase-03-admin-catalog.md`)
- [ ] T3.1 Admin auth and shell
- [ ] T3.2 Shared admin widgets (Livewire/Blade)
- [ ] T3.3 Attributes
- [ ] T3.4 Brands
- [ ] T3.5 Categories
- [ ] T3.6 Products list
- [ ] T3.7 Product edit

## Phase 4 — Admin: content and settings  (`phase-04-admin-content.md`)
- [ ] T4.1 Page content editor
- [ ] T4.2 Services
- [ ] T4.3 Projects
- [ ] T4.4 Blog
- [ ] T4.5 Content pages
- [ ] T4.6 Settings and users

## Phase 5 — Public site: layout and content pages  (`phase-05-public-content.md`)
- [ ] T5.1 Global layout
- [ ] T5.2 WhatsApp click logging
- [ ] T5.3 Home
- [ ] T5.4 About
- [ ] T5.5 Services and service detail
- [ ] T5.6 Projects
- [ ] T5.7 Blog and article
- [ ] T5.8 Brands and brand detail
- [ ] T5.9 Content pages
- [ ] T5.10 SEO basics

## Phase 6 — Public site: catalog  (`phase-06-catalog-front.md`)
- [ ] T6.1 Category page
- [ ] T6.2 Products listing (Livewire)
- [ ] T6.3 Product card
- [ ] T6.4 Product detail
- [ ] T6.5 Search

## Phase 7 — Cart, checkout, payments, orders  (`phase-07-commerce.md`)
- [ ] T7.1 Cart
- [ ] T7.2 Checkout
- [ ] T7.3 Confirmation, payment failed, track order
- [ ] T7.4 InstaPay via WhatsApp
- [ ] T7.5 Paymob driver
- [ ] T7.6 Admin: orders
- [ ] T7.7 Admin: coupons, delivery, payments, leads, consultations, newsletter, dashboard
- [ ] T7.8 Notifications

## Phase 8 — Customer accounts  (`phase-08-accounts.md`)
- [ ] T8.1 Auth screens
- [ ] T8.2 Linking and merging
- [ ] T8.3 Account area
- [ ] T8.4 Wiring
- [ ] T8.5 Admin: customers

## Phase 9 — Performance and operations  (`phase-09-performance-ops.md`)
- [ ] T9.1 Query and cache review
- [ ] T9.2 Images
- [ ] T9.3 Backups and scheduler
- [ ] T9.4 Security and errors

## Phase 10 — QA and deployment  (`phase-10-qa-deploy.md`)
- [ ] T10.1 Content coverage report
- [ ] T10.2 Full test pass and manual QA
- [ ] T10.3 Deployment guide

## Phase 11 — Deferred (start only when unblocked)  (`phase-11-deferred.md`)
- [ ] T11.1 Products Excel import/export
- [ ] T11.2 Google Drive backups

## Decisions log
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

## Phase reports
- **Phase 0 — Review (2026-10-07).** Reviewed CLAUDE.md, PRD, ERD, CONTENT_MAP, design/README + spot-checked 10+ design JSX strings against the `page_sections`/CONTENT_MAP schemas (all matched). Confirmed all 48 ERD tables are covered by phase tasks. Wrote `docs/plan/REVIEW.md` (gaps, risks, package proposals, 11 questions). Owner answered all questions; approved edits applied to PRD, ERD, CONTENT_MAP and this Decisions log. Found and fixed a setup blocker: the project was not its own git repo (git root was the home dir) — ran `git init` in `Herd/dots` with a basic `.gitignore`, home `~/.git` left untouched. Phase 0 complete; awaiting `start phase 1`.
- **Phase 1 — Foundation (2026-10-07).** Running Laravel 13 app with locales, design CSS, layouts, auth guards and tooling. Checklist all green.
  - **T1.1** Laravel 13.35 created in a temp folder and moved in over the existing CLAUDE.md/docs/design (restored the project CLAUDE.md after the installer overwrote it; removed the stray AGENTS.md). Installed Livewire 4, Spatie translatable/medialibrary/permission/backup/sitemap, mews/purifier, Pest 4. `.env.example` with MySQL+Redis, `APP_LOCALE=ar`, empty `PAYMOB_*`. Exact versions in Decisions.
  - **T1.2** `/{locale}` group (ar default/en), `/`→`/ar`, `SetLocale` middleware (app+Carbon locale, `url()` default, shares locale/dir), `Locale` switch-URL helper, `HasTranslatableSlug` route-binding trait.
  - **T1.3** Dropped Tailwind. Two Vite entries (site/admin), design CSS ported verbatim, @fontsource self-hosted fonts, Alpine 3, `<x-icon>` over `mallardduck/blade-lucide-icons` with the Icon.jsx RTL-flip list. Local styleguide.
  - **T1.4** Public + admin layout shells and 16 design-primitive Blade components; UI-only ar/en lang files; kitchen-sink page.
  - **T1.5** `admin` + `customer` guards, shared `LoginThrottle` (5 attempts / 15-min lock), DB session driver, bilingual `auth` lang.
  - **T1.6** Larastan level 6, Pint, strict models outside prod, composer `test`/`lint`/`analyse`.
  - **Verification:** `composer test` 25 passed · `composer analyse` 0 errors · `pint --test` clean · `npm run build` ok · app serves `/ar`, `/en`, kitchen sink (HTTP 200) via Herd. No secrets committed; `vendor/`, `node_modules/`, `public/build/`, `bootstrap/cache/*` ignored.
  - **Carried to later phases:** customer password-reset broker (Phase 8); confirm FULLTEXT ngram search (Phase 2/6); remove the HasTranslatableSlug phpstan exclude once a Phase 2 model consumes it.

