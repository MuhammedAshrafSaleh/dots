# DOTS — Phase 0 review report

Status: **awaiting owner answers.** No application code written, no doc edited (this file only).

## What was reviewed
- `CLAUDE.md`, `docs/PRD.md` (full), `docs/ERD.md` (full), `docs/CONTENT_MAP.md` (full), `design/README.md`.
- Spot-checked design JSX against CONTENT_MAP / `page_sections` schemas: Home hero + featured tabs (`Home.jsx`), global quote band bullets/hours (`Shared.jsx`), checkout sections + payment options (`components/commerce/CheckoutForm.jsx`), About stats (`About.jsx`), services 4-step process (`Services.jsx`), product specs/trust/VAT line (`Products.jsx`), track-order + "save your details" (`Orders.jsx`). All match the schemas.
- **ERD coverage:** all 48 tables are referenced by at least one phase task. Every table is created in `phase-02-database.md`; most also appear in a feature phase. No orphan tables.

I can explain the data model and the page list without reopening the docs. Verdict: PRD/ERD/CONTENT_MAP/design are **highly consistent**. The items below are mostly small gaps, copy clean-ups the design README already anticipated, and implementation decisions to lock before coding — not rewrites.

---

## 0. Blocker — project is not its own git repository
`git rev-parse --show-toplevel` → `/Users/muhammedashraf` (home), branch `refactor/layout-separation` (an unrelated Flutter project), **0 tracked files under `dots/`, no `.git` in this folder.**

The workflow in CLAUDE.md requires a commit per task (`T<phase>.<n>: …`). Committing now would write into the accidental home-level repo. **I have not committed.**

**Proposed:** `git init` inside `/Users/muhammedashraf/Herd/dots` (add a `.gitignore` for `vendor/`, `node_modules/`, `.env`, `storage/*`, `public/build/`), make the first commit, and ideally stop tracking the whole home directory. Please confirm before I commit anything.

---

## (a) Contradictions & gaps

**Copy the design README already flags "no SMS", needs a defined replacement**
1. **SMS wording in live copy.** `Orders.jsx` track lede = "Enter the order number from your confirmation **SMS**…"; `CheckoutForm.jsx` InstaPay option = "Reference sent by **SMS**". These are UI labels (go to `lang/*`), not DB, so they are not in CONTENT_MAP — but the AR/EN strings must be reworded to WhatsApp/email. Need the approved wording (see Q8).
2. **Checkout extra payment options.** `CheckoutForm.jsx` renders Fawry, "InstaPay/Fawry · SMS", and Instalments. Out of scope per `design/README.md`; the real list is the 3 rows in `payment_methods` (`cod`, `card`, `instapay_whatsapp`). No action beyond building from the table — noting so it isn't ported verbatim.
3. **"Upload BOQ / drawings" button.** Design shows a 25 MB file-upload toast. PRD forbids customer uploads → this must become a `wa.me` link logged as `quote_requests.source = boq`. No upload control.
4. **Spec groups.** `Products.jsx` renders grouped specs (Physical/Optical/Electrical). ERD/PRD say flat `product_specs` (name + value). Already flagged OOS — build flat.

**Real schema / data-model gaps**
5. **Order number sequence has no home.** PRD: "`DT-` + sequence, start 10001". `settings.commerce.order_prefix` also exists (two sources of truth for the prefix). There is no counter column/table in the ERD. Needs a generation strategy and a decision on prefix authority (see Q2).
6. **`page_sections` buttons missing link targets.** `global.mega_promo` has `button_label` but no `button_link`; `about.hero` has `button1_label`/`button2_label` but no link fields. Both render a clickable button. Likely need `*_link` fields added (see Q5). (By contrast `home.hero` and `home.smart_banner` do include link fields.)
7. **`order_addresses` is 1:1 (unique) but pickup orders have no address.** When `delivery_methods.requires_address = false`, no address row is created. The unique index allows the row to be absent, so this works — just confirming the relationship is treated as nullable and checkout validation skips address fields for pickup.

**Consistent — verified, not issues (listed so they aren't re-raised)**
8. `faqs` polymorphic (`faqable_type` = category | service | null) cleanly serves category buying-guide FAQs, service-detail FAQs, and the general services-page FAQ.
9. Brand page category tabs and "Specified by Dots" projects are both derivable (`products.category_id` ancestors; `project_product → products → brand`) with no manual link table, as PRD intends.
10. `stock_status` enum has no "out" value on purpose — qty 0 → `order` ("On order" to customers); admin "out of stock" is a qty view, not a status.
11. `services.process` `page_section` is shown on both `/services` and each service detail (CONTENT_MAP) → the 4 steps are **global/shared**, per-service copy is only `duration_text`. Confirm (Q7).

---

## (b) Technical risks

1. **Per-locale unique translatable slugs.** `slug` is `json {ar,en}`; a plain `UNIQUE` index can't enforce "unique per locale" on a JSON column, and route-model binding by locale slug needs a custom resolver. **Mitigation:** add MySQL **generated STORED columns** `slug_ar`/`slug_en` (extracted from the JSON) with `UNIQUE` indexes, bind routes against those, plus app-level validation. Touches migrations only (not ERD intent) — needs approval (Q3).
2. **Arabic slug generation.** `Str::slug()` strips Arabic and yields empty/colliding slugs. Need a Unicode-preserving slugger (keep `\p{Arabic}\p{L}\p{N}`, collapse whitespace to `-`, lowercase Latin, append `-2`… on collision). Decide Arabic-script vs transliterated slugs (Q4).
3. **Localized routing + language switch.** Switching locale must keep the user on the **equivalent** page, i.e. resolve the current model then swap to its other-locale slug. Requires a `SetLocale` middleware + a per-model "counterpart URL" helper. Recommend a thin custom layer over `spatie/laravel-translatable` rather than a routing package (none handle translated slugs cleanly).
4. **Arabic FULLTEXT search.** InnoDB FULLTEXT default `innodb_ft_min_token_size = 3` hides 1–2 char tokens and can miss short SKUs/brand fragments; tokenisation of mixed AR/EN/SKU text is imperfect. **Mitigation:** evaluate the `ngram` parser and/or a `LIKE`-based fallback for SKUs; verify on the 10k-product seeder with `EXPLAIN`.
5. **Facet counts + Redis tag cache at 10k+ products.** Correct invalidation (category tree, brand lists, facet counts, filtered pages) via model observers is the main correctness risk. Must test invalidation, not just speed.
6. **VAT-inclusive rounding.** VAT 14% is included in displayed prices; define rounding rules (round line totals vs order total) to avoid off-by-a-piaster mismatches between cart, order, and snapshots.
7. **Rich-HTML sanitization.** `overview`, `project.about`, `post.body`, `page.body` store sanitized HTML. Sanitizer allowlist must permit the needed blocks (h2/h3, lists, blockquote, image-with-caption, callout) and nothing else; sanitize on save.
8. **Media conversions on the queue.** WebP/`srcset` conversions and emails need a running queue worker; dev must fall back to sync so nothing silently stalls.
9. **Guest → account linking edge cases.** Linking on both-mobile-and-email match collides with `customers.mobile`/`email` being unique. Define behaviour when the email/mobile already belongs to a different account (Q10).
10. **MySQL version drift.** Local is **MySQL 9.6** (Homebrew); PRD/production target MySQL 8. Fine (superset) but avoid 9.x-only syntax so migrations run on 8.0 (Q9).
11. **Paymob flow undecided** (Intention/Unified vs legacy Accept) — blocks Phase 7 card tasks; already pending in PRD §15 (Q1).

---

## (c) Proposed packages & approaches

**Framework / runtime** (local verified: PHP 8.4.23, Composer 2.10, MySQL 9.6, Node 22, npm 10)
- **Laravel 12** (latest stable), **PHP 8.3+** (8.4 local), **Livewire 3**, **Alpine 3**, **Vite 5**, **Pest 3**.
- Redis via **phpredis** if available, else **predis**; `CACHE_STORE`/`QUEUE_CONNECTION`/`SESSION_DRIVER` switchable in `.env` with database fallback (keep `cache`, `jobs`, `sessions` tables).

**Spatie (per PRD)**
- `laravel-translatable`, `laravel-medialibrary`, `laravel-permission`, `laravel-backup`, `laravel-sitemap`.
- **Slugs:** `spatie/laravel-sluggable` with a translatable slug source + the custom Arabic slugger above.

**Rich text + sanitizer**
- Editor: **TipTap** (StarterKit + Image, plus a custom "callout" mark), mounted in a small Alpine component, bundled via Vite. Flexible enough for image-with-caption and callout. (Trix is the simpler fallback if you prefer zero JS config — Q6.)
- Sanitizer: **`mews/purifier`** (HTMLPurifier) with an explicit allowlist per field. (`symfony/html-sanitizer` is the lighter alternative; I recommend HTMLPurifier for the fine-grained allowlist incl. callout classes.)

**Localization & slugs approach**
- Routes under `Route::prefix('{locale}')->whereIn('locale', ['ar','en'])`; `SetLocale` middleware sets `app()->setLocale()` and `<html dir>`; `ar` default.
- Translatable slugs resolved via custom route binding against generated `slug_ar`/`slug_en` columns; a `localizedUrl($model)` helper powers the language switch to the counterpart slug.
- Unicode-preserving Arabic slugger (keep Arabic letters, per-locale uniqueness suffixes).

**Deferred** (PRD §15): `maatwebsite/excel` → Phase 11.

---

## (d) Questions for the owner
1. **Paymob flow:** Intention / Unified Checkout (recommended) or legacy Accept (Auth→Order→Payment key→iframe)? Blocks Phase 7 card tasks.
2. **Order number:** is the prefix fixed `DT-`, or driven by `settings.commerce.order_prefix` (which wins)? OK to implement the 10001 start via a dedicated counter?
3. **Slug enforcement:** OK to add generated `slug_ar`/`slug_en` columns + unique indexes for per-locale uniqueness and routing? (Enforcement only; doesn't change ERD intent.)
4. **Arabic slugs:** keep Arabic-script slugs in AR URLs (e.g. `/ar/products/كشاف-سقف`) — recommended, matches PRD — or ASCII-transliterate?
5. **Button links:** add `button_link` to `global.mega_promo` and `button1_link`/`button2_link` to `about.hero`? If not, what fixed targets should those buttons use?
6. **Rich-text editor:** TipTap (recommended) or Trix? Any blocks needed beyond h2/h3, p, ul/ol, blockquote, image+caption, callout?
7. **Services process:** confirm the 4 "how we work" steps are global/shared across all services (as in the design), with only `duration_text` per service.
8. **SMS copy:** approve rewording the track-order lede and confirmation copy to "order confirmation (WhatsApp/email)" — what exact AR/EN wording do you want?
9. **MySQL target:** production on MySQL 8.0 (local is 9.6)? OK to code to 8.0 compatibility.
10. **Guest→account linking:** when the order's email or mobile already belongs to a different customer, block linking with a message (recommended) or merge?
11. **Git/repo:** confirm I should `git init` inside `Herd/dots` and commit there (see §0) — and that the home-directory repo tracking is unintended.

---

## Proposed doc edits (hold until you answer — I will not touch the docs before then)
- **ERD:** add `*_link` fields to `global.mega_promo` / `about.hero` section schemas (Q5); add a note on per-locale slug generated columns + unique indexes (Q3); note the order-number counter mechanism (Q2).
- **CONTENT_MAP:** no structural change expected; add the mega-promo / about-hero link rows if Q5 approved.
- **PRD:** reconcile `DT-` prefix vs `settings.order_prefix` wording (Q2); add the "no-SMS → WhatsApp/email" confirmation copy wording (Q8).
- **lang files (Phase 1):** ensure no "SMS" strings; BOQ button = WhatsApp, not upload.
