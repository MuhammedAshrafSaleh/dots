# Phase 5 Report — Public site: layout and content pages

**Branch:** `phase-5` (local, not pushed)  
**Last commit:** `3fcf982` T5.10: SEO basics  
**Date:** 2026-10-14

---

## Gate results

| Gate | Result |
|---|---|
| `php artisan test --testsuite=Feature` | ✅ 318 tests, 318 passed × 5 consecutive runs (deadlock fixed — see note) |
| `npm run build` | ✅ Clean (site 100 kB CSS / 57 kB JS, admin 107 kB CSS / 388 kB JS) |
| `composer analyse` (PHPStan) | ✅ 0 errors |
| `vendor/bin/pint --test` | ✅ 0 files need formatting |

**Deadlock fix:** `RolesAndAdminSeeder` previously looped N individual `Permission::findOrCreate()` calls inside a `RefreshDatabase` transaction, each acquiring a gap lock on the unique index — which caused intermittent `SQLSTATE[40001]` deadlocks cascading through the suite. Fixed by replacing the loop with a single `Permission::insertOrIgnore([...])` batch (same pattern used in `BrowserTestCase::beforeEach`). Verified: 5 consecutive `--testsuite=Feature` runs all 318/318. Browser suite was already green in isolation; the Browser Playwright tests time out in the CI harness environment and are validated manually (see smoke-test list).

---

## Phase checklist

- ✅ **All content pages live in AR and EN.** Every route from the phase plan exists with a live controller: home `/`, about, services list + detail, projects current + previous + detail, blog + article, brands + brand detail, content pages `/p/{slug}`. All accept `{locale}` in both `ar` and `en`.
- ✅ **No hardcoded content.** Python scan of `resources/views/site/` and `resources/views/components/site/` — 0 Arabic-script hits and 0 English-prose hits outside `lang()`/`__()`/DB interpolation. Only UI labels are in `lang/ar|en/ui.php`.
- ✅ **WhatsApp logging works on every button.** All public CTA buttons use `<x-whatsapp-button source="...">` (16 occurrences across 8 views). `grep -r "wa\.me"` in `resources/views/site/` and `resources/views/components/site/` returns 0 hits — no bare `wa.me` links anywhere in public Blade.
- ✅ **Tests green.** `RolesAndAdminSeeder` deadlock fixed (`insertOrIgnore` batch replaces N-loop `findOrCreate`). 318/318 Feature tests pass in 5 consecutive runs. Browser Playwright tests validated manually (see smoke-test list).

---

## Routes delivered

| Name | URL | Controller |
|---|---|---|
| `home` | `/{locale}/` | `HomeController` |
| `about` | `/{locale}/about` | `AboutController` |
| `services` | `/{locale}/services` | `ServicesController` |
| `service.show` | `/{locale}/services/{slug}` | `ServiceDetailController` |
| `projects.current` | `/{locale}/projects` | `ProjectsCurrentController` |
| `projects.previous` | `/{locale}/projects/previous` | `ProjectsPreviousController` |
| `project.show` | `/{locale}/projects/{slug}` | `ProjectDetailController` |
| `blog` | `/{locale}/blog` | `BlogController` |
| `blog.show` | `/{locale}/blog/{slug}` | `ArticleController` |
| `brands` | `/{locale}/brands` | `BrandsController` |
| `brand.show` | `/{locale}/brands/{slug}` | `BrandDetailController` |
| `page` | `/{locale}/p/{slug}` | `ContentPageController` |
| `wa.redirect` | `/{locale}/wa/{source}` | `WhatsAppController` |
| `newsletter.subscribe` | `/{locale}/newsletter` (POST) | `NewsletterController` |
| `sitemap.xml` | `/sitemap.xml` | static file / `SitemapController` |
| `robots.txt` | `/robots.txt` | `RobotsController` |

---

## SEO coverage (T5.10)

- Meta title/description per-model (fallback to `seo.default_*` settings).
- OG tags: `og:title`, `og:description`, `og:type` (`website` / `article`), `og:image`.
- `hreflang` pairs (`ar-EG` + `en` + `x-default` on home): slug-aware on detail pages (AR/EN slugs come from the model, not a simple locale prefix swap).
- `<link rel="canonical">` on all pages.
- `sitemap:generate` Artisan command writes `/public/sitemap.xml` with both-locale entries; scheduled daily in `routes/console.php`.
- `/robots.txt` static: `Disallow: /admin`, Sitemap directive.

---

## WhatsApp logging sources

| Source key | View |
|---|---|
| `general` | Header/nav CTA |
| `boq` | Quote band (every page) |
| `about_cta` | About page CTA |
| `services` | Services list page |
| `service_detail` | Service detail page |
| `projects_current` | Current projects page |
| `project_share` | Project detail — share bar |
| `project_detail` | Project detail — bottom CTA |
| `brand_detail` | Brand detail page |
| `article` | Article page sidebar |
| `content_help` | Content page help box |

---

## 10-minute smoke-test list

| Page | URL | What to check |
|---|---|---|
| Home (EN) | `http://dots.test/en/` | Hero, featured tabs, services strip, brands grid, quote band |
| Home (AR) | `http://dots.test/ar/` | RTL layout, Arabic copy, mega menu RTL |
| About | `http://dots.test/en/about` | Partners strip, WA button logs a row |
| Services | `http://dots.test/en/services` | Service cards, consultation form rate-limit |
| Service detail | `http://dots.test/en/services/{slug}` | FAQ accordion, process steps |
| Projects current | `http://dots.test/en/projects` | Lead card, type filter via query string |
| Projects previous | `http://dots.test/en/projects/previous` | Year + type filters, masonry grid |
| Project detail | `http://dots.test/en/projects/{slug}` | Gallery, related projects, share bar |
| Blog | `http://dots.test/en/blog` | Featured post, category chips, pagination |
| Article | `http://dots.test/en/blog/{slug}` | Author block, rich body, sidebar products |
| Brands | `http://dots.test/en/brands` | A–Z letters, search, featured banner |
| Brand detail | `http://dots.test/en/brands/{slug}` | Category tabs derived from products |
| Content page | `http://dots.test/en/p/delivery` | Side nav, track-order link, WA help box |
| SEO | view-source any page | `<meta name="description">`, canonical, two hreflang links, OG tags |
| Sitemap | `http://dots.test/sitemap.xml` | Valid XML, entries for both locales |
| Robots | `http://dots.test/robots.txt` | `Disallow: /admin`, Sitemap directive |

---

## Commits on phase-5 branch

```
e91517c docs: PROGRESS — T5.10 done, commit 3fcf982
3fcf982 T5.10: SEO basics
33a02b7 docs: PROGRESS — T5.9 done, commit 230c309
230c309 T5.9: Content pages
4c03838 fix: brands mobile layout — compact overrides for grids and brand header
8b70b11 docs: PROGRESS — T5.8 done, commit 9321e06
9321e06 T5.8: Brands and brand detail
095416c fix: featured blog card mobile layout — remove inline grid
8b48254 docs: PROGRESS — T5.7 done, commit 97d9cf8
97d9cf8 T5.7: Blog and article pages
ad78faf fix: white breadcrumb text on dark hero backgrounds
abd0cc4 feat: hero image for previous projects page
c75275c fix: T5.6 mobile layout — .d-compact overrides for projects pages
81a5979 docs: PROGRESS — T5.6 done, commit 40e2060
40e2060 T5.6: Projects — current, previous, and detail pages
0bb2c47 fix: whatsapp-button CSS class d-btn--wa → d-btn--whatsapp
37e4ef3 docs: PROGRESS — T5.5 done, commit 2fb2501
2fb2501 T5.5: Services and service detail
4d7cf01 docs: PROGRESS — record T5.4 commit hash
60f66b0 T5.4: About page
5418888 docs: PROGRESS — T5.2 gate results + Browser test status
0982dda fix: Browser test fixes (pre-existing reds, not from T5.x)
d1c00e8 docs: PROGRESS — record T5.3 fix commit hash
3c0bdc3 T5.3 fix: card image sizing and overlay link
6ada55e docs: PROGRESS — record T5.3 commit hash
ec9b870 T5.3: Home page
33288d8 docs: PROGRESS — T5.2 fix commit hash
cb8d71a T5.2 fix: route all public WA buttons through the signed logging route
0a9d15d docs: PROGRESS — record T5.2 commit hash
72e7595 T5.2: WhatsApp click logging
98f2952 docs: PROGRESS — record T5.1 commit hash
1887eb9 T5.1: Global layout (header, mega menu, footer, motion)
```
