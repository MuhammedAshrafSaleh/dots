# Phase 3 — Admin: shell, shared widgets, catalog

**Goal:** Admins can log in and fully manage products, categories, brands and attributes.

Rules: read only this file + PROGRESS.md at session start; open refs only when a task needs them; one commit per task; STOP at the end of the phase.

## T3.1 — Admin auth and shell
- Login, forgot/reset password, remember me, lockout (ALogin). Sidebar groups and topbar exactly as ASidebar/ATopbar (global search: orders, SKUs, customers). Language switch in admin.
- Permission middleware on every admin route.
- **Design/docs:** admin-kit.jsx: ALogin, ASidebar, ATopbar
- **Done when:** Tests: guest redirected; customer guard cannot access /admin.

## T3.2 — Shared admin widgets (Livewire/Blade)
- Data table (search, filters, sort, bulk actions, pagination, empty state) like ATable/AList.
- Bilingual field (ABi), slugs per locale (ASlugs, auto-generate + editable), SEO card (ASeo), image upload with preview (AImage), gallery with drag order + captions (AGallery), file row (AFile), rich text editor with sanitizer (ARich: headings, lists, quote, image+caption, callout), repeater (ARepeat), product picker (AProductPicker), icon picker (AIconPick), drawer (ADrawer), switches, toasts, confirm dialogs.
- **Design/docs:** admin-kit.jsx building blocks
- **Done when:** Each widget has a Livewire test or a feature test through a screen.

## T3.3 — Attributes
- List + edit (AAttributes/AAttributeEdit): code, name, display type, unit, filterable, variant axis, values table with label + swatch picker + drag order.
- **Design/docs:** admin-kit.jsx: AAttributes, AAttributeEdit
- **Done when:** CRUD tests.

## T3.4 — Brands
- List + edit (ABrands/ABrandEdit) with every field in ERD `brands` incl. catalogue PDF, featured (only one), authorised, partners, homepage, SEO.
- **Design/docs:** admin-kit.jsx: ABrands, ABrandEdit
- **Done when:** Only one featured brand enforced.

## T3.5 — Categories
- Tree with expand/collapse and drag order within a level (ACategories/ACPanel). Edit panel: all ERD fields incl. FAQ repeater, top picks picker, homepage/footer switches, SEO. Delete moves products to parent (confirm).
- **Design/docs:** admin-kit.jsx: ACategories, ACPanel
- **Done when:** Tests: reorder, delete moves products.

## T3.6 — Products list
- AProducts: tabs by status, search (name/SKU/brand), filters (category, brand, stock), sort, bulk publish/archive/set badge/delete, duplicate, view on store. Import button hidden until the import phase.
- **Design/docs:** admin-kit.jsx: AProducts
- **Done when:** Tests for filters and bulk actions.

## T3.7 — Product edit
- AProductEdit tabs: Basic (name, overview, variants summary, warranty, base SKU), Category & brand (one brand, one leaf), Variants (price on request on top, axes from Attributes, generated combinations table: SKU, price, compare-at, stock, low threshold, default, active; single default row when no axes), Specs (flat name/value bilingual, drag), Images (captions, drag, main), Files (4 slots + extra with title), Related (often together + cart suggestions), Badges & flags, SEO (slug per locale).
- Visibility card with status Draft/Published/Archived.
- **Design/docs:** admin-kit.jsx: AProductEdit, APVariants, APSpecs, APImages, APFiles
- **Done when:** Tests: variant generation, SKU uniqueness, cache columns updated on save.

## Phase checklist
- [ ] Admin login + permissions
- [ ] Catalog screens match the admin design
- [ ] CONTENT_MAP rows for catalog have an admin field
- [ ] Tests green
