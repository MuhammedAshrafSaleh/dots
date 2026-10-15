# Phase 6 Report — Public site: catalog

**Branch:** `phase-6`  
**Date completed:** 2026-10-15  
**Gate result:** PASSED — 394 tests (392 passed, 2 skipped), 0 Larastan errors, Pint clean, Vite build OK.

---

## Tasks

| Task | Commit | Summary |
|------|--------|---------|
| T6.1 Category page | `0720142` | Hero, sub-category tiles with cached counts, top picks, buying guide, FAQ, brand chips |
| T6.2 Products listing | `8f57db3` | KProducts Livewire component, FilterSidebar, ActiveFilters, facet counts, sort/grid/list, URL state |
| T6.3 Product card | `a35a12d` | Badges (sale %, new, price-on-request), swatches, stock label, WhatsApp quote, wishlist |
| T6.4 Product detail | `3cf30ad` | Gallery with thumbs, VariantPicker, price/SKU/stock per variant, tabs, related products |
| T6.5 Search | `c62bb08` | Header search → results page, FULLTEXT index, Arabic normalisation (alef/yaa/taa marbuta) |

---

## Fixes applied during the phase

- `ff2adb7` — Cast translatable caption array to locale string in gallery
- `7d79893` — Correct category route name in product-detail breadcrumb
- `4580f54` — Allow class unserialization from Redis cache (`serializable_classes`)
- `83a60f6` — CacheTags::remember: evict stale `__PHP_Incomplete_Class` DB cache entries

---

## Decisions

- Facet counts cached by filter-hash tags via `CacheTags::remember`; invalidated by model observers.
- FULLTEXT search index on `products.search_text` (generated column built from all translatable fields). Arabic normalisation applied at write-time (index) and query-time.
- Browser E2E tests paused per CLAUDE.md; behavior covered by feature tests.

---

## How to review

1. `php artisan migrate:fresh --seed`
2. Open dots.test — check catalog pages:
   - Category page: `/ar/categories/{slug}` and `/en/categories/{slug}`
   - Products listing with filters: `/ar/products` and `/en/products`
   - Product detail with variant switching: click any product
   - Search: type in header bar → Enter → `/en/search?q=...`
   - Arabic search: "اضاءة" and "إضاءة" should both return lighting products
3. `composer gates` — all green.

---

## Commits ahead of main

```
085ff7a chore: update PROGRESS.md with T6.5 done, commit c62bb08
c62bb08 T6.5: Search — FULLTEXT with Arabic normalisation, search results page
0f8e458 chore: update PROGRESS.md with commit ff2adb7
ff2adb7 fix: cast translatable caption array to locale string in gallery
7d79893 fix: correct category route name in product-detail breadcrumb
afce1e0 docs: update PROGRESS.md with T6.4 done, commit 3cf30ad
3cf30ad T6.4: Product detail — gallery, VariantPicker, tabs, related
17f8818 docs: PROGRESS — T6.3 done, commit a35a12d
a35a12d T6.3: Product card — badges, swatches, stock, actions, wishlist
b9b8063 docs: update PROGRESS.md with commit hash d80bd64
d80bd64 chore: exclude browser tests from default gates, add composer gates script
ce08cd1 docs: PROGRESS — Redis serializable_classes fix, commit 4580f54
4580f54 fix: allow class unserialization from Redis cache (serializable_classes)
a875944 chore: add DB safety guard + CLAUDE.md rules to prevent dev-DB wipe
83a60f6 fix: CacheTags::remember — evict __PHP_Incomplete_Class stale DB cache entries
2da4d7e docs: PROGRESS — T6.2 done, commit 8f57db3
8f57db3 T6.2: Products listing (Livewire)
06c2315 chore: add strict test-running rules to CLAUDE.md
0720142 T6.1: Category page
```
