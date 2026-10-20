# Bulk product import/export — BI.1 → BI.3

**Goal:** Admin bulk import & export of products/variants/specs + media, pulled forward from Phase 11 (T11.1) for the client meeting. Full spec: `docs/IMPORT_SPEC.md`.

**Branch:** `bulk-import` (off up-to-date main, local only). After BI.3 the next step is **Phase 10** (Phase 9 is already merged/tagged). Release works like a phase: on owner's "approved, push bulk-import" → push branch, `git merge --no-ff` into main, tag `bulk-import-done`, push main --tags.

**Rules (review-gated, same as phases):** one task per session, in order. After each task `composer gates` must pass (unit+feature+Larastan+Pint+build) → tick it here → **commit locally** `BI.<n>: <title>` (one commit per task, no push). Browser tests stay paused — write feature/Livewire tests instead, except the single explicit E2E named in BI.3. When a task touches UI/Livewire, list exactly what the owner clicks on dots.test. Update PROGRESS.md "Current state" before stopping. STOP after each task for owner review.

Reuse anchors: `App\Actions\UpdateProductCacheColumns`, `App\Support\CacheTags`, `App\Support\Slug`, `App\Support\Html` (purifier), product media collections `gallery` + `files` with slots `datasheet/ies/ldt/guide` (`app/Livewire/Admin/Products/Edit.php`), permissions in `config/permissions.php`.

---

## BI.1 — Template generator, Download template, Export, Import page shell  _(Sonnet)_
- [x] Done

Install `maatwebsite/excel`. Build the template generator (§1–§2 of IMPORT_SPEC): 8 sheets, Arabic row-1 headers + reused cell comments, hidden row-2 keys, live dropdowns (brands, active level-3 category paths, per-axis attribute values, yes/no + status), dynamic `attr:<code>` columns, numeric validations. Add the Import page shell under `app/Livewire/Admin` with a **"Download template"** button and an **Export** button producing the same format. Add `products.import` + `products.export` to `config/permissions.php`; gate the page/buttons.

**Acceptance criteria**
- Admin (with permission) sees Products → Import with "Download template" + "Export"; both download valid `.xlsx`.
- Generated template dropdowns contain the **live** brands / category paths / attribute values; `attr:` columns match current `is_variant_axis` attributes.
- All `DataValidation` list formulas have **no leading `=`**.
- Export mirrors the template sheets/keys; `images` column empty; formula-injection cells escaped.
- Page/buttons hidden without the permission.

**Required tests (feature/Livewire)**
- Dropdown list ranges resolve to live brands, category paths and attribute values (seed a few, assert present).
- Every generated validation formula does not start with `=`.
- Export of a product whose text field is `=1+1` is written as `'=1+1` (escaped).
- Authorization: user without `products.import`/`products.export` gets 403 / buttons absent.

**Owner clicks (dots.test):** log in as Admin → Products → Import → "Download template" (open it, confirm sheets + dropdowns), then "Export" (open it). No 403/500/JS errors.

---

## BI.2 — Parse, validate, preview, report download (dry run, no writes)  _(Sonnet)_
- [ ] Done

Parse the uploaded xlsx mapping **by row-2 key, never by position** (§1). Validate every rule in §3–§4 and §7 without writing anything. Produce the preview: counts (create/update, variants, specs, images matched) + an errors/warnings table (sheet, row, column, Arabic message) + a downloadable **.xlsx report**. Buttons "Apply valid rows" / cancel (Apply is wired in BI.3). Media is parsed only enough to report matched/unmatched here; no extraction yet.

**Acceptance criteria**
- Column mapping is purely key-based (reordered columns still import; renamed row-1 headers don't break it).
- Preview shows correct counts and a full error/warning table with Arabic messages; report downloads.
- **Nothing is written to the DB** during preview.

**Required tests (feature)**
- `EXAMPLE-` product/variant SKUs rejected as row errors.
- Unknown brand / unknown category path rejected.
- Simple-vs-variant conflict (variant rows + product-level price) rejected.
- Bad numbers (non-numeric price, negative stock, `compare_at_price` ≤ `price`) rejected.
- Header keys mapped not positional: a file with shuffled column order parses correctly.
- After a preview run, assert zero rows created/updated in `products`/`product_variants`/`product_specs`.

**Owner clicks (dots.test):** Products → Import → upload a filled xlsx (include a bad row) → see preview counts + error table → download the report. Confirm nothing changed in the catalog.

---

## BI.3 — Apply job + ZIP media  _(Opus)_
- [ ] Done

Implement the queued **Apply** job (§4–§7): upsert products/variants/specs, variant sync + deactivation, specs replace-all, cache recompute + flush, single DB transaction, import log, then attach media after commit. Full ZIP media pipeline (§6): private extraction, all limits/security, basename/SKU matching, gallery replace, file slots, warnings. Configure Livewire upload rules/sizes and document the Herd PHP settings (§8).

**Acceptance criteria**
- Valid rows applied in one transaction; media attached after commit; import log recorded (who/when/file/counts/report).
- Cache recomputed + caches flushed; public page reflects changes immediately.
- All media rules & limits enforced; media problems are warnings, not row failures.

**Required tests (feature)**
- Update with an empty cell keeps the current value.
- A variant missing from the file is set `is_active=false`, **not deleted**.
- Rejected: zip-slip path entry; oversized ZIP (over configured limit); fake-extension image (wrong real MIME).
- A listed image missing from the ZIP → warning **and** the product is still imported.
- A NEW product that ends with zero valid images → forced to `draft`.
- `<script>` inside `overview_*` is stripped (purifier).
- A price change is visible on the public product page immediately (cache flushed).
- Export → re-import of the same file = **zero changes**.

**Required test (the one allowed browser E2E, Pest 4 + Playwright):** download template → fill 2 products → upload xlsx + zip → preview → apply → both products visible on the site **with images**.

**Owner clicks (dots.test):** Products → Import → upload xlsx + ZIP → preview → "Apply valid rows" → watch progress → open the imported products on the public site (prices + images show, deactivated variants gone from the picker). Check the import log entry.

---

## Checklist
- [x] BI.1 done
- [ ] BI.2 done
- [ ] BI.3 done
