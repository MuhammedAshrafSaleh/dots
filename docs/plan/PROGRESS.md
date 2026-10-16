# PROGRESS

## Current state _(read this first — a new session resumes from here)_
- **Branch:** `phase-7` (local only; branched from main after phase-6 merge).
- **Current task:** T7.7 (Admin: coupons, delivery, payments, leads, consultations, newsletter, dashboard) **done**. Next: `start T7.8` (Notifications — new-order email to settings contact, queued).
- **Last commit:** `b5a5899` (T7.7).
- **What's new (T7.7):** seven admin screens, all behind named `can:` permissions, each with a feature test for its main action:
  - **Coupons** (`/admin/coupons`) — table + create/edit drawer (code/generate, percent-or-fixed radio, value, min order, start/end, usage limit, once-per-customer, active), toggle active inline, delete (keeps redemptions). Percentage > 100 rejected.
  - **Delivery** (`/admin/delivery`) — drag-to-reorder list + drawer (icon pick, bilingual name/description/note, price, "from" price, free-over, requires-address, governorate chips with select-all/clear, active). Empty govs = serves everywhere.
  - **Payments** (`/admin/payments`) — read-only Paymob status (reads `PaymentGateway::isConfigured()` from env) + read-only webhook URL; InstaPay address + hold hours saved to `settings` (`commerce.instapay_address` / `commerce.instapay_hold_hours`); edit name/description/instructions/order; drag reorder. **Card cannot be enabled while Paymob is unconfigured** (toggle + save both guard).
  - **Leads** (`/admin/leads`) — QuoteRequests: tabs by LeadStatus + source/date filters + search + CSV export; drawer edits name/mobile/status/internal-notes; staff-only wa.me per row.
  - **Consultations** (`/admin/consultations`) — ConsultationRequests: same shell, type filter; drawer edits name/mobile/status/internal-notes; CSV export.
  - **Newsletter** (`/admin/newsletter`) — subscribers: subscribed/unsubscribed tabs + source/language filters + search; unsubscribe/resubscribe/delete; CSV export gated behind `newsletter.export`.
  - **Dashboard** (`/admin`) — KPIs (today's orders, month revenue from paid orders, WhatsApp clicks today, new consultations) + recent orders, low-stock products, latest leads; quick links retained.
- **Notes:** CSV exports stream with UTF-8 BOM and honour active filters (same pattern as AOrders). Added `@property` docblocks to QuoteRequest / ConsultationRequest / NewsletterSubscriber for enum casts (larastan). The `<x-admin.switch>` component (not `<x-switch>`) is the toggle. DemoSeeder already seeds coupons, delivery/payment methods, leads (one per QuoteSource), consultations, and newsletter subscribers, so every screen has data.
- **Open issues / observations:** Pre-existing FLAKY test unrelated to T7.7 — `tests/Feature/Admin/Attributes/AttributesCrudTest.php:27` `assertDontSee('cct')` intermittently fails when the random Livewire component id contains `cct`. Passes on re-run; untouched. The uncommitted `database/seeders/SiteSeeder.php` change (whatsapp number) is the owner's, left unstaged. (T7.6's `order_status_histories.internal_note` migration still needs `php artisan migrate` on the dev DB if not already run.)
- **What to review on dots.test** (run `php artisan migrate:fresh --seed` first for demo data, or at least `php artisan migrate`):
  - Open each of `/admin/coupons`, `/admin/delivery`, `/admin/payments`, `/admin/leads`, `/admin/consultations`, `/admin/newsletter`, and `/admin` (dashboard). No 403/500/JS errors.
  - **Coupons:** "New coupon" → fill + save; toggle a coupon's active switch; delete one. Edit an existing coupon (drawer prefilled).
  - **Delivery:** drag rows to reorder (order persists on reload); open a method, toggle governorate chips + "Select all"/"Clear", save; toggle active inline.
  - **Payments:** card row shows "Not configured" badge + warning (empty Paymob env); try to enable card → it refuses with a toast. Open InstaPay → change address/hold hours, save; reopen to confirm persisted. Webhook URL is read-only.
  - **Leads / Consultations:** tabs + filters + search; open a row → change status, edit name/mobile, add internal note, save; "Export CSV" downloads a filtered, Arabic-readable file; wa.me icon opens a chat.
  - **Newsletter:** subscribed/unsubscribed tabs + source/language filters; unsubscribe then resubscribe a row; "Export CSV".
  - **Dashboard:** KPI cards show live counts; recent orders / low stock / latest leads lists link through.

**Gate command:** `composer gates` (unit+feature+larastan+pint+build; no browser tests). T7.7 gate: 488 passed / 2 skipped, phpstan 0, pint clean, build ok.

**Git:** branch `phase-7` · last commit `b5a5899` (T7.7) · last tag `phase-6-done` · push state: phase-7 not pushed (local only)

Current phase: **7 — Cart, checkout, payments, orders** — T7.7 done

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

Legend: [ ] todo · [x] done

---

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
- **2026-10-15 — Redis cache serialization (T6.2 fix):** Laravel 13 defaults `config/cache.php` `serializable_classes => false`, which makes `RedisStore` unserialize with `allowed_classes => false` — every cached Eloquent model/collection comes back as `__PHP_Incomplete_Class` (500 on `/products`). Tests never caught it because the test env uses the `array` cache store (no serialization). This app intentionally caches its own Eloquent collections (category tree, brand/attribute facets) in a private Redis store, so set `serializable_classes => env('CACHE_SERIALIZABLE_CLASSES', true)`. Requirement: production Redis must stay non-public (the setting is only safe when the cache store is not attacker-writable).
