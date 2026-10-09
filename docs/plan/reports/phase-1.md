# Phase 1 Report — Foundation (2026-10-07)

Running Laravel 13 app with locales, design CSS, layouts, auth guards and tooling. Checklist all green.

- **T1.1** Laravel 13.35 created in a temp folder and moved in over the existing CLAUDE.md/docs/design (restored the project CLAUDE.md after the installer overwrote it; removed the stray AGENTS.md). Installed Livewire 4, Spatie translatable/medialibrary/permission/backup/sitemap, mews/purifier, Pest 4. `.env.example` with MySQL+Redis, `APP_LOCALE=ar`, empty `PAYMOB_*`. Exact versions in Decisions.
- **T1.2** `/{locale}` group (ar default/en), `/`→`/ar`, `SetLocale` middleware (app+Carbon locale, `url()` default, shares locale/dir), `Locale` switch-URL helper, `HasTranslatableSlug` route-binding trait.
- **T1.3** Dropped Tailwind. Two Vite entries (site/admin), design CSS ported verbatim, @fontsource self-hosted fonts, Alpine 3, `<x-icon>` over `mallardduck/blade-lucide-icons` with the Icon.jsx RTL-flip list. Local styleguide.
- **T1.4** Public + admin layout shells and 16 design-primitive Blade components; UI-only ar/en lang files; kitchen-sink page.
- **T1.5** `admin` + `customer` guards, shared `LoginThrottle` (5 attempts / 15-min lock), DB session driver, bilingual `auth` lang.
- **T1.6** Larastan level 6, Pint, strict models outside prod, composer `test`/`lint`/`analyse`.

**Verification:** `composer test` 25 passed · `composer analyse` 0 errors · `pint --test` clean · `npm run build` ok · app serves `/ar`, `/en`, kitchen sink (HTTP 200) via Herd. No secrets committed; `vendor/`, `node_modules/`, `public/build/`, `bootstrap/cache/*` ignored.

**Carried to later phases:** customer password-reset broker (Phase 8); confirm FULLTEXT ngram search (Phase 2/6); remove the HasTranslatableSlug phpstan exclude once a Phase 2 model consumes it.
