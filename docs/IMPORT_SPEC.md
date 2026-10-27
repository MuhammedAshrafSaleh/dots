# IMPORT_SPEC — Bulk product import / export

Authoritative spec for DOTS bulk product import & export. Implemented across **BI.1–BI.3** (see `docs/plan/bulk-import.md`). Pulled forward from Phase 11 (T11.1). Reference template: `docs/import/DOTS-products-import-template.xlsx`.

Principles: the importer **maps columns by the hidden row-2 system key, never by position**; it **never creates** brands, categories or attribute values; it **never deletes** products; DATA problems skip a row, MEDIA problems only warn.

---

## 1. Template layout

One `.xlsx` with 8 sheets (Arabic titles). Row 1 = Arabic header (required columns are **yellow + `*`**, optional are grey) with an explanatory **cell comment** on every documented column (reuse the exact comments from the reference file). Row 2 = **hidden system keys** — the importer maps by these. **Data starts at row 3.** Example rows use `EXAMPLE-` SKUs and a grey italic style; they must be deleted before upload (and are rejected anyway — see §4).

| Sheet (title) | Purpose | State |
|---|---|---|
| `تعليمات` | Instructions (text only, col B) | visible |
| `المنتجات` | Products — one row per product | visible |
| `الاختيارات` | Variants — one row per variant | visible |
| `المواصفات` | Specs — one row per spec line | visible |
| `البراندات` | Brands list (dropdown source) | visible |
| `الأقسام` | Categories list, full Arabic path (dropdown source) | visible |
| `قيم الاختيارات` | Attribute values per axis (dropdown source) | visible |
| `lists` | yes/no + status enums (dropdown source) | **hidden** |

### 1.1 `المنتجات` (Products) — header ↔ key

| Key (row 2) | Header (row 1) | Required | Maps to |
|---|---|---|---|
| `sku` | كود المنتج * | ✓ | `products.sku` (upsert key) |
| `name_ar` | اسم المنتج بالعربي * | ✓ | `products.name.ar` |
| `name_en` | اسم المنتج بالإنجليزي * | ✓ | `products.name.en` |
| `brand` | البراند * | ✓ | `products.brand_id` (match by name, dropdown) |
| `category` | القسم * | ✓ | `products.category_id` (full Arabic path of level-3, dropdown) |
| `status` | الحالة | | `products.status` (منشور/مسودة/مؤرشف) |
| `price` | السعر (ج.م) | | simple-product `product_variants.price` |
| `compare_at_price` | السعر قبل الخصم (ج.م) | | simple-product `product_variants.compare_at_price` |
| `stock_qty` | الكمية في المخزن | | simple-product `product_variants.stock_qty` |
| `price_on_request` | السعر عند الطلب | | `products.price_on_request` (نعم/لا) |
| `overview_ar` | نظرة عامة بالعربي | | `products.overview.ar` (plain text → `<p>`) |
| `overview_en` | نظرة عامة بالإنجليزي | | `products.overview.en` |
| `warranty_ar` | الضمان بالعربي | | `products.warranty.ar` |
| `warranty_en` | الضمان بالإنجليزي | | `products.warranty.en` |
| `is_new` | جديد | | `products.is_new` (نعم/لا) |
| `is_best_seller` | الأكثر مبيعاً | | `products.is_best_seller` |
| `is_smart_pick` | منزل ذكي | | `products.is_smart_pick` |
| `is_dimmable` | قابل للتعتيم | | `products.is_dimmable` |
| `ships_24h` | شحن خلال 24 ساعة | | `products.ships_24h` |
| `images` | الصور (أسماء الملفات) | | media `gallery` (ZIP basenames, `|`-separated) |
| `meta_title_ar` | عنوان SEO بالعربي | | `products.meta_title.ar` |
| `meta_title_en` | عنوان SEO بالإنجليزي | | `products.meta_title.en` |
| `meta_description_ar` | وصف SEO بالعربي | | `products.meta_description.ar` |
| `meta_description_en` | وصف SEO بالإنجليزي | | `products.meta_description.en` |

### 1.2 `الاختيارات` (Variants) — header ↔ key

| Key (row 2) | Header (row 1) | Required | Maps to |
|---|---|---|---|
| `product_sku` | كود المنتج * | ✓ | parent `products.sku` (must exist in Products sheet) |
| `variant_sku` | كود الاختيار * | ✓ | `product_variants.sku` (upsert key, ≠ product sku) |
| `attr:<code>` | (attribute name, e.g. درجة اللون (K)) | | one column per `is_variant_axis` attribute → `product_variant_values` |
| `price` | السعر (ج.م) * | ✓* | `product_variants.price` (*empty only if price_on_request) |
| `compare_at_price` | السعر قبل الخصم (ج.م) | | `product_variants.compare_at_price` |
| `stock_qty` | الكمية في المخزن * | ✓ | `product_variants.stock_qty` |
| `is_default` | الاختيار الافتراضي | | `product_variants.is_default` (نعم/لا) |

`attr:<code>` columns are generated dynamically — one per attribute with `is_variant_axis = true` (reference file ships `attr:cct`, `attr:wattage`, `attr:size`, `attr:finish`). The importer discovers them by the `attr:` key prefix, not by position or count.

### 1.3 `المواصفات` (Specs) — header ↔ key

| Key (row 2) | Header (row 1) | Required | Maps to |
|---|---|---|---|
| `product_sku` | كود المنتج * | ✓ | parent `products.sku` |
| `name_ar` | اسم المواصفة بالعربي * | ✓ | `product_specs.name.ar` |
| `name_en` | اسم المواصفة بالإنجليزي * | ✓ | `product_specs.name.en` |
| `value_ar` | القيمة بالعربي * | ✓ | `product_specs.value.ar` |
| `value_en` | القيمة بالإنجليزي * | ✓ | `product_specs.value.en` |

Row order within a product = `product_specs.sort_order`.

### 1.4 Dropdown source sheets

- `البراندات` — col A, brand names from row 3 down.
- `الأقسام` — col A, full Arabic path of **active level-3** categories (`إضاءة > داخلية > داون لايت`), row 3 down.
- `قيم الاختيارات` — one column per axis attribute; row 1 = Arabic attribute name, row 2 = attribute `code`, values from row 3 down (label.ar if set else value; **unique per attribute**).
- `lists` (hidden) — `A1:A2` = `نعم` / `لا`; `B1:B3` = `منشور` / `مسودة` / `مؤرشف`.

---

## 2. Template generation (admin → "Download template")

The template is **generated by the system**, not hand-maintained, via `maatwebsite/excel` (PhpSpreadsheet) so the dropdowns always reflect live data. Admin: **Products → Import → "Download template"** (permission `products.import`).

- Brands sheet ← all brands by name. Categories sheet ← full Arabic path of active level-3 categories. `قيم الاختيارات` ← one column per `is_variant_axis` attribute, values = label.ar if present else `value`, de-duplicated per attribute. `attr:<code>` columns on `الاختيارات` are generated to match.
- **Data-validation dropdowns** use `DataValidation` list formulas pointing at those sheets, e.g. `'البراندات'!$A$3:$A$1000`, `'قيم الاختيارات'!$A$3:$A$200`, `lists!$A$1:$A$2`, `lists!$B$1:$B$3`. **Formulas must NOT start with `=`** (PhpSpreadsheet requirement — a leading `=` breaks the dropdown). Covered by a BI.1 test.
- Numeric validations: `price`/`compare_at_price` = decimal ≥ 0; `stock_qty` = whole ≥ 0.
- Row-1 comments are re-applied from a fixed map (the strings in the reference file). Required headers styled yellow + `*`.

The example (`EXAMPLE-`) rows are **not** emitted by the real generator — they exist only in the hand-made reference file for illustration.

---

## 3. Reference data is never created

Brands, categories and attribute values are **chosen from the dropdowns**, never invented. An unknown brand / category path / attribute value → **row error**: _"غير موجود — أضفه من لوحة التحكم ثم نزّل القالب من جديد"_ ("add it in admin and re-download the template"). The import never writes to `brands`, `categories`, `attributes` or `attribute_values`.

---

## 4. Data rules

**Keys & identity**
- Product upsert key = `products.sku`. Variant upsert key = `product_variants.sku`.
- Any SKU starting with `EXAMPLE-` (product or variant) → **row error** (example rows weren't deleted).

**Simple vs variant products**
- **Simple product** = a Products row with no matching rows in `الاختيارات`. The importer ensures **exactly one default variant whose `sku` = the product sku**, taking `price`/`compare_at_price`/`stock_qty` from the Products row.
- **Variant product** = has ≥1 row in `الاختيارات`. Then Products `price`, `compare_at_price`, `stock_qty` **must be empty**, else **row error**.
- An existing product that already has **multiple variants** receiving a product-level `price` → **row error**.

**Variant sync** (for products present in the file)
- Upsert each variant by `variant_sku`.
- Variants of that product **missing from the file → `is_active = false`** (never deleted).
- Each variant needs **≥1 attribute value**; the attribute-value combination must be **unique within the product**.
- Exactly **one `is_default`** per product; if none flagged, the **first row** wins.

**Specs**
- If a product has rows in `المواصفات`, they **replace all** its existing specs (order = `sort_order`). No spec rows → specs **untouched**.

**Create vs update**
- **Update:** an empty cell **keeps the current value** (never blanks it).
- **Create:** `status` defaults to **draft**; slugs auto-generated per locale via `App\Support\Slug` (`app/Support/Slug.php`, Arabic-preserving) and made unique; `variants_summary` auto-generated if empty.

**Types & validation**
- Booleans accept `نعم / لا / yes / no / 1 / 0 / true / false` (case-insensitive).
- Money `decimal(12,2)`; `compare_at_price` must be **> `price`** when present; stock is a **whole number ≥ 0**.
- `price` may be empty **only if** `price_on_request = yes` (else row error).
- `overview_ar`/`overview_en`: **plain text only**. Each line → one escaped `<p>`, then run through the existing purifier (`App\Support\Html`, mews/purifier). Raw HTML is never accepted.
- The importer **never deletes products**.

---

## 5. Cache & visibility

After apply, for every touched product recompute cache columns via `App\Actions\UpdateProductCacheColumns` (`min_price`, `max_compare_at`, `stock_status`, `search_text`, `variants_summary`) and flush the relevant tags via `App\Support\CacheTags::flush(LISTINGS, FACETS, CATEGORIES, BRANDS)` — matching `ProductObserver`/`ProductVariantObserver`. An imported price/stock change **must show on the public product page immediately** (respect the Phase 9 caching layer). If writes bypass model events, call the action + flush explicitly. Covered by a BI.3 test.

---

## 6. Media (optional ZIP, uploaded alongside the xlsx)

Media is **optional**. One ZIP per upload.

**Entry handling**
- Match by **basename only** (a zipped folder works). Matching is **case-insensitive**.
- Ignore `__MACOSX/`, `._*`, `.DS_Store`, `Thumbs.db`.
- **Duplicate basenames** anywhere in the ZIP → **error for the whole ZIP**.
- Handle both UTF-8 and non-UTF-8 filename encodings. Template instructions recommend **English filenames**.

**Security / limits**
- Extract to a **private temp dir** (never public); **delete after** the job.
- Configurable limits (config file): max ZIP size, max total **uncompressed** size, max entries, max per-file size (zip-bomb guard).
- Reject **path traversal** (zip-slip).
- Allowed: images `jpg/jpeg/png/webp`; files `pdf`, `ies`, `ldt`. **Verify real MIME by content**, not extension.

**Matching to products**
- If a product's `images` cell lists filenames (separated by `|`), use them **in order** (first = main gallery image).
- Else auto-match by SKU: `SKU.ext`, `SKU-2.ext`, `SKU-3.ext`… (first = main).
- File slots by suffix → the 4 named slots from T4.8 (`app/Livewire/Admin/Products/Edit.php`): `SKU-datasheet.pdf` → **datasheet**, `SKU-ies.ies` → **ies**, `SKU-ldt.ldt` → **ldt**, `SKU-manual.pdf` → **guide** (installation guide).
- Images supplied for a product → **replace** its gallery. None supplied → **keep** existing. **No URL downloads** (no Drive/web links).

---

## 7. Error policy

- **DATA problems = row ERRORS** → that row is **skipped**. A product row error also skips that product's variant and spec rows.
- **IMAGE / FILE problems = WARNINGS** → the product is still imported **without** that file. Warnings cover: a listed file missing from the ZIP; corrupt or fake image; file too large; disallowed type. A **NEW** product that ends with **zero valid images is forced to `draft`** (the warning says so). ZIP entries matching **no** product → warning.

**Preview (dry run — nothing is written)**
- Counts: products to create / update, variants, specs, images matched.
- Table of errors + warnings: **sheet, row, column, Arabic message**.
- Downloadable **.xlsx report** of that table.
- Buttons: **"Apply valid rows"** / **cancel**.

**Apply**
- **Queued job** with progress.
- All valid rows committed in **one DB transaction**; **media attached after commit**.
- **Import log**: who, when, file name, counts, report.
- Fixing images later = **re-upload the same Excel** with a ZIP containing only the fixed images.

---

## 8. Upload limits & Herd PHP settings

- Configure Livewire temporary-upload max size + rules for the `.xlsx` and `.zip` inputs.
- Owner must raise local Herd PHP limits so large uploads work. Recommended values (set in the Herd PHP `php.ini` for the active version, then restart Herd):
  - `upload_max_filesize = 64M`
  - `post_max_size = 80M`  (must exceed `upload_max_filesize` + the xlsx)
  - (if needed for big catalogs) `max_execution_time = 120`, `memory_limit = 512M`
- Recommend **splitting large catalogs by brand** to stay under limits and keep previews fast.

---

## 9. Permissions

Add to `config/permissions.php` (single "Admin" role holds all):
- `products.import` — download template, upload, preview, apply.
- `products.export` — download the current-data export.

---

## 10. Export

- Produces the **same sheets and row-2 keys** filled with current data.
- The `images` column is **left empty** — re-import never touches media.
- **Round-trip safe**: export → re-import makes **zero changes**.
- **Formula-injection escape**: any cell whose value starts with `=`, `+`, `-` or `@` is prefixed with a single quote `'` on export. Covered by a BI.1 test (`=1+1` exported escaped).
