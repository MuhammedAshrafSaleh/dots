# Phase 6 — Public site: catalog

**Goal:** Fast, filterable catalog and product pages.

Rules: read only this file + PROGRESS.md at session start; open refs only when a task needs them; one commit per task; STOP at the end of the phase.

## T6.1 — Category page
- KCategory: hero, sub-category tiles with counts, top picks, buying guide + FAQ, brand chips.
- **Design/docs:** Brands.jsx: KCategory
- **Done when:** Counts cached.

## T6.2 — Products listing (Livewire)
- KProducts + FilterSidebar + ActiveFilters: category tree with counts, brands (searchable, show all), filterable attributes (chips/swatches), price range, in-stock, ships in 24h; sort (recommended, price asc/desc, newest); grid/list; 12/page; all state in the query string (`#[Url]`); mobile filter drawer with count; skeleton/loading states.
- Facet counts computed with efficient grouped queries and cached by filter hash (tags).
- **Design/docs:** Products.jsx: KProducts; components/catalog/*
- **Done when:** Tests for each filter; EXPLAIN on LargeCatalogSeeder shows index use; listing < 300 ms warm.

## T6.3 — Product card
- ProductCard: badges (sale % auto, new, project/price on request), swatches, variants summary, stock label, add to cart (Phase 7 wiring), WhatsApp quote, wishlist heart (prompt when logged out).
- **Design/docs:** components/cards/ProductCard.jsx
- **Done when:** Price-on-request card shows quote as primary.

## T6.4 — Product detail
- Gallery with thumbs, brand link, SKU + stock qty of selected variant, price + compare-at, VAT note, variant pickers (VariantPicker/SwatchPicker) with disabled combos (Livewire), qty, add to cart, WhatsApp quote (logs variant + qty), wishlist button, trust row, downloads, tabs (overview, specs, downloads, delivery & returns from settings), often specified together.
- Schema-free (no rich snippets) per scope.
- **Design/docs:** Products.jsx: KProductDetail; Account.jsx: KFavButton
- **Done when:** Tests: variant switch updates price/SKU/stock; disabled combos not orderable.

## T6.5 — Search
- Header search results page reusing the listing with a `q` param and scope; FULLTEXT on search_text with Arabic normalisation (alef/yaa/taa marbuta) applied to both index text and query.
- **Design/docs:** SearchBar.jsx
- **Done when:** Arabic search finds variants of spelling.

## Phase checklist
- [ ] Catalog matches design
- [ ] Perf target met on 10k products
- [ ] Tests green
