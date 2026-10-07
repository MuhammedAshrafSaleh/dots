# Phase 1 — Foundation

**Goal:** A running Laravel app with locales, design CSS, layouts, auth guards and tooling. No business features yet.

Rules: read only this file + PROGRESS.md at session start; open refs only when a task needs them; one commit per task; STOP at the end of the phase.

## T1.1 — Create the project
- The folder already holds CLAUDE.md, docs/ and design/: create the Laravel app (latest stable, PHP 8.3+) in a temporary folder and move it in without touching those. MySQL + Redis configured. Git init; keep design/ in git but add design/**/assets/*.mp4 to .gitignore if the repo host limits size.
- Install: livewire, spatie/laravel-translatable, spatie/laravel-medialibrary, spatie/laravel-permission, spatie/laravel-backup, spatie/laravel-sitemap, the HTML sanitizer chosen in REVIEW, Pest.
- .env.example: DB, Redis, mail, APP_LOCALE=ar, CACHE_STORE=redis, QUEUE_CONNECTION=redis, and empty PAYMOB_* placeholders (PAYMOB_SECRET_KEY, PAYMOB_PUBLIC_KEY, PAYMOB_API_KEY, PAYMOB_INTEGRATION_ID_CARD, PAYMOB_HMAC_SECRET, PAYMOB_IFRAME_ID).
- **Design/docs:** PRD §2
- **Done when:** `php artisan test` passes on a fresh clone; no secrets committed.

## T1.2 — Locales and routing
- Route group `/{locale}` (ar|en), `/` redirects to `/ar`. Middleware sets app locale, `dir`, Carbon locale.
- Helper to build the same URL in the other locale (used by the language switch), including translated slugs.
- Translatable slug route binding helper (lookup by `slug->{locale}`).
- **Design/docs:** PRD §3
- **Done when:** Feature test: /ar and /en render with correct lang/dir; switching keeps the path.

## T1.3 — Front-end build and design CSS
- Vite entry for the site and a separate one for the admin.
- Port design/website/styles/styles.css and design/admin/styles/styles.css verbatim into resources/css (split into partials only if it helps). Self-host fonts with @fontsource (Sora, Manrope, IBM Plex Sans Arabic, IBM Plex Mono).
- Alpine.js installed; Lucide icons as an SVG Blade component (`<x-icon name="..."/>`) with the RTL-flip list used in the design Icon.jsx.
- **Design/docs:** design/website/styles, design/admin/styles, design/website/src/components/core/Icon.jsx
- **Done when:** A test page renders design buttons, badges, inputs identical to the design in both directions.

## T1.4 — Base layouts and Blade components
- Public layout (head/meta slots, body classes, toasts container) and admin layout (sidebar + topbar shell, no data yet).
- Blade components for the design primitives: button, icon-button, badge, chip, eyebrow, input, select, checkbox, radio-card, quantity-stepper, tabs, accordion, breadcrumbs, pagination, image-frame, toast.
- Translation files lang/ar and lang/en with UI labels only.
- **Design/docs:** design/website/src/components/**
- **Done when:** Components render in a kitchen-sink page (local env only).

## T1.5 — Auth guards
- Guard `admin` (users table) and guard `customer` (customers table, created in Phase 2 migrations — add config now).
- Login throttling helper: 5 attempts, 15-minute lock, reused by both guards.
- Sessions on the database driver (needed to log out other devices).
- **Design/docs:** PRD §9, §11
- **Done when:** Config in place; tests for throttling helper.

## T1.6 — Quality tooling
- Pint, Larastan (level 5+), Pest with RefreshDatabase, `Model::preventLazyLoading()` outside production, strict mode.
- composer scripts: test, lint, analyse.
- **Done when:** `composer test && composer analyse` green.

## Phase checklist
- [x] /ar and /en work with RTL/LTR
- [x] Design CSS and fonts load
- [x] Components kitchen sink matches design
- [x] Guards configured
- [x] Tooling green
