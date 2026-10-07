# DOTS — Product requirements (final, approved)

DOTS is a bilingual (Arabic RTL primary, English LTR) catalog + e-commerce site for lighting, smart home and electrical supplies in Egypt, with WhatsApp quote requests, services, projects portfolio and a blog. Built with Laravel. Every page matches the Claude Design export in `design/`.

Related docs: `docs/ERD.md` (database), `docs/CONTENT_MAP.md` (every visible field → table/column → admin screen), `design/README.md` (design file map).

## 1. Scope rules
1. Build every page and element that exists in the design, nothing that does not. Exceptions listed in §14.
2. **No hardcoded content.** Every heading, text, image, number and link listed in CONTENT_MAP.md is read from the database and editable in the admin. Only UI labels (buttons, form labels, validation and error messages, empty states) live in `lang/ar/*.php` and `lang/en/*.php`.
3. **No file uploads from customers**, anywhere. Only admins upload files.
4. **No SMS anywhere.** Order confirmation and follow-up happen on WhatsApp (manually by staff). Password reset is by email.
5. All money in EGP, VAT 14% included in displayed prices.

## 2. Stack
- Latest stable Laravel, PHP 8.3+, MySQL 8, Redis (cache + queue + sessions optional), Vite.
- Public pages: Blade (server-rendered for SEO). Livewire for: product filters/sort/search, cart drawer and cart page, checkout, customer account area, and the whole admin. Alpine.js for motion, menus, tabs, accordions, galleries. Livewire handles its own AJAX: no hand-written API for these.
- Styling: port `design/website/styles/styles.css` and `design/admin/styles/styles.css` as-is (tokens + `d-*` / `a-*` classes). **No Tailwind.** Fonts self-hosted via @fontsource: Sora, Manrope, IBM Plex Sans Arabic, IBM Plex Mono.
- Packages: spatie/laravel-translatable, spatie/laravel-medialibrary, spatie/laravel-permission, spatie/laravel-backup, spatie/laravel-sitemap, an HTML sanitizer for rich text, Pest. maatwebsite/excel comes later (§15).
- Admin UI built by hand (Blade + Livewire + Alpine). **Filament is not allowed.**

## 3. Locales and URLs
- Every public route under `/{locale}` with `ar` (default) and `en`. `<html dir>` follows the locale.
- Slugs are translatable: each locale has its own slug (Arabic slugs for Arabic URLs). Language switch keeps the user on the equivalent page.
- `hreflang`, canonical, per-page meta title/description (from the model's SEO fields or `settings.seo` defaults), Open Graph tags, `sitemap.xml` for both locales.

## 4. Public pages (design file in `design/README.md`)
| # | Page | Route (inside /{locale}) |
|---|---|---|
| 1 | Home | `/` |
| 2 | Products list (all + filtered) | `/products` |
| 3 | Category page | `/categories/{slug}` |
| 4 | Product detail | `/products/{slug}` |
| 5 | Brands | `/brands` |
| 6 | Brand detail | `/brands/{slug}` |
| 7 | Services | `/services` |
| 8 | Service detail + consultation form | `/services/{slug}` |
| 9 | Current projects | `/projects/current` |
| 10 | Previous projects | `/projects/previous` |
| 11 | Project detail | `/projects/{slug}` |
| 12 | Blog | `/blog` |
| 13 | Article | `/blog/{slug}` |
| 14 | About | `/about` |
| 15 | Cart | `/cart` |
| 16 | Checkout | `/checkout` |
| 17 | Order confirmation | `/orders/{number}/thanks` (signed or session-guarded) |
| 18 | Payment failed | `/orders/{number}/payment-failed` |
| 19 | Track order | `/track-order` |
| 20 | Content page (Delivery, Returns, Warranty, Careers, Terms, Privacy) | `/pages/{slug}` |
| 21–24 | Login, Register, Forgot password, Reset password | `/login`, `/register`, `/forgot-password`, `/reset-password/{token}` |
| 25–30 | Account: overview, orders, order detail, wishlist, addresses, profile | `/account`, `/account/orders`, `/account/orders/{number}`, `/account/wishlist`, `/account/addresses`, `/account/profile` |

Shared on every page: utility strip, header (search with scopes, language switch, account menu, cart count), category mega menu (3-level tree + promo), projects dropdown, cart drawer, quote band, footer (newsletter, link columns from DB, socials, payments line), toasts, preloader / page curtain / custom cursor / scroll reveal (respect `prefers-reduced-motion`).

## 5. Catalog
- **Categories**: one global 3-level tree (main + icon → group → leaf). Shared by all brands. A product belongs to one leaf category; it appears in its ancestors too. Category page: hero image, description, sub-category tiles with counts, top picks (admin-picked), buying guide title + FAQ, brand chips (derived).
- **Brands**: brand page tabs = main categories that contain that brand's products, with counts (derived, no manual brand↔category link). "Specified by Dots" = projects that used the brand's products.
- **Products**: name, SKU, brand, leaf category, overview, variants summary, warranty, gallery (with captions), downloadable files (datasheet, IES, LDT, manual, extra), specs (flat list name + value, per product), related products ("often specified together") and cart suggestions ("don't forget"), flags (new arrival, best seller, smart home pick, dimmable, ships in 24h), recommended sort weight, status draft/published/archived.
- **Variants**: axes come from global Attributes (CCT, wattage, size, finish… with swatches). Each variant: SKU, price, compare-at price, stock qty, optional low-stock threshold, default, active. Price/SKU/stock change with the selection on the product page; specs do not. Unavailable combinations are disabled.
- **Price on request**: product shows "Price on request", no add-to-cart; WhatsApp quote becomes the primary action.
- **Stock status** (cached on product): in stock / low (≤ threshold) / on order (qty 0). Admin may display "out of stock" internally; customers see "On order".
- **Products list** (Livewire): facets = category tree with counts, brands (searchable, counts, show all), each filterable attribute, price range, in-stock only, ships in 24h. Active filter chips, clear all, result count. Sort: recommended, price asc, price desc, newest. Grid/list toggle. 12 per page. All state in the query string (shareable, crawlable). Mobile filter button with count.
- **Search**: header search with scope (All, Lighting, Smart home, Electrical, Brands). Matches name (both locales), SKUs, brand via FULLTEXT `search_text`.
- **Wishlist**: heart on product cards and product page. Logged-out click shows the small "log in to save" prompt (design `WishlistPrompt`).

## 6. Cart, coupons, checkout
- Cart works for guests (session) and customers (`carts` table, merged on login). Cart drawer + cart page (Livewire): qty stepper, remove, subtotal, coupon, delivery, total, VAT note, "Quote this cart on WhatsApp", suggestions.
- **Coupons are manual only**: the customer types the code. Percent or fixed, min order total, start/end, usage limit, optional once per registered customer. Discount applies to subtotal before delivery. Never auto-applied.
- **Checkout** (single page, design `CheckoutForm`): no guest/login choice screen. Logged-out: slim line "Have an account? Log in". Logged-in: prefilled contact + saved address cards + "Use a new address". Sections: contact (name, Egyptian mobile, optional email, company order + tax ID), address (governorate, city/district, street & building, floor/apartment, landmark; not required when the delivery method does not require an address), delivery method, payment method, summary.
- **Delivery methods are fully admin-defined**: name, description, optional note, icon, price, "from" price flag, free-over amount, requires-address flag, allowed governorates (none = all), order, active. No fixed methods in code. Checkout shows only methods valid for the chosen governorate; free-over applies automatically.
- **Payment methods** (admin can edit name, description, customer instructions, order, active):
  1. Cash on delivery: order created, payment `pending` until staff marks paid. Independent of Paymob.
  2. Card via Paymob: hidden automatically when credentials are missing from `.env`. Credentials are **never** stored in the DB or editable in the admin.
  3. InstaPay via WhatsApp: order created with payment `awaiting_transfer`, items reserved until `reserved_until` (hours from settings), customer sent to WhatsApp with order number, items, delivery and total. Staff confirms payment in the admin. A scheduled job releases expired reservations (cancels the order and restores stock).
- Stock is decremented when the order is placed (and restored on cancel/expiry).
- Order number `DT-` + sequence (start 10001). Snapshot product name, variant label, SKU, unit price, image, governorate and delivery method name on the order.

## 7. Orders, tracking, notifications
- Two independent statuses: order `new → confirmed → preparing → shipped → delivered` or `cancelled`; payment `pending | awaiting_transfer | paid | failed | refunded`. Every change writes `order_status_histories` (time + optional customer-visible note + admin user).
- **Tracking**: the order number is the tracking number. Track page = order number + mobile → status badges, progress, timeline with times and notes, optional expected delivery date, items, total. No couriers, no AWB.
- Order confirmation page: three variants by payment (COD, card paid, InstaPay "one step left" + Continue on WhatsApp). Guests see "Save your details for next time" (single password field; name, mobile, email from the order) — the only place an account is created from an order.
- Payment failed page: reason (Paymob code/message), retry card, switch to COD, switch to InstaPay via WhatsApp.
- New order → email to admin (address in settings). Order detail in admin has "Send update on WhatsApp" (opens a prefilled `wa.me` chat from the `tpl_order_update` template). No automatic WhatsApp/SMS to customers.

## 8. Paymob
- `PaymentGateway` interface + `PaymobGateway` driver. Config from `.env` only (empty placeholders in `.env.example`). Default flow: **Intention API (Unified Checkout)**; ask the owner to confirm before implementing (legacy Accept flow = Auth → Order → Payment key → iframe is the alternative).
- Callback/webhook: verify HMAC, store `webhook_events` (unique event id → idempotent), update `payments` (transaction id, card brand/last4, failure code/message, raw) and order payment status. Redirect customer to thanks or payment-failed.
- Card option hidden when not configured; admin Payments screen shows "Configured on the server" / "Not configured" and the read-only webhook URL.

## 9. Customer accounts
- Separate guard and table (`customers`); customers can never reach `/admin`.
- Register: full name (first + last), Egyptian mobile (010/011/012/015 + 8 digits), email, password (≥ 8 chars incl. a number), accept terms (links to Terms and Privacy content pages).
- Login with mobile **or** email + password, remember me. Rate limit: 5 attempts then 15-minute lock. Forgot password by email link (single use, 30 minutes). Password change logs out other devices.
- Guest orders link to an account only when **both mobile and email** match (at registration and at "save your details").
- Account area: overview (member since, latest order with progress, wishlist preview, tiles), my orders, order detail (timeline, items, payment summary, address, payment method, Reorder = add same variants at current prices, WhatsApp help), wishlist (add all to cart), addresses (label home/office/other, recipient, mobile, governorate, city, street, floor/apartment, landmark, default), profile (edit name/mobile/email directly, no verification code; change password).
- Disabled customers cannot log in.

## 10. WhatsApp and forms
- Every WhatsApp button hits an internal route that logs a `quote_requests` row (source, related model, variant/qty or cart snapshot, order, customer, page URL, locale, IP, user agent, session) and then redirects to `wa.me/<settings.contact.whatsapp_number>?text=<template>`. Logged even if the customer never sends the message.
- Sources: product, cart, instapay_order, boq (the "Upload BOQ / drawings" button opens WhatsApp — no upload), brand, service, project, article, order_help, general.
- Contact name/mobile on a lead are auto-filled when known (customer logged in or order), otherwise staff fill them after the chat.
- Templates (bilingual, editable, with variables): product, cart, instapay_order, boq, general, order_update.
- Consultation form (service detail): name, mobile, project type, area range, notes → `consultation_requests`. Newsletter (footer + blog): email → `newsletter_subscribers`. All public forms: validation, honeypot, rate limit.

## 11. Admin (`/admin`)
- Separate login (email + password, remember, forgot password, lockout). Single role **Admin** via spatie/laravel-permission; every admin route/action still checks a named permission so roles can be added later without rewriting.
- Screens (design `design/admin/src/admin-kit.jsx`): Dashboard; Catalog: Products (list, bulk publish/archive/badge/delete, edit with tabs), Categories (tree, drag order), Brands, Attributes; Content: Page content (per-page sections from `config/page_sections.php`), Services, Projects, Blog (posts, categories, tags), Content pages; Sales: Orders (list, filters, detail with status/payment update, customer-visible note, internal note, expected delivery, Paymob log, WhatsApp update), Customers (list, detail, disable), Coupons; Leads: WhatsApp leads, Consultations, Newsletter (CSV exports); Settings: Delivery methods, Payment methods, Settings (Contact, Commerce, WhatsApp templates, Footer, SEO), Users (admins).
- Every customer-facing text field is bilingual; every image/file field previews and can be replaced/removed.

## 12. Performance
- Indexes as in ERD.md; verify the filter queries with `EXPLAIN` on seeded data (≥ 10k products seeder for testing).
- Redis cache with tags for: category tree + counts, brand lists, facet counts, filtered listing pages, page sections, settings. Invalidate by model observers. `CACHE_STORE`/`QUEUE_CONNECTION` switchable in `.env` (database fallback works without code changes).
- Images: Media Library conversions to WebP in several sizes, responsive `srcset`, lazy loading. Queue for conversions and emails.
- Eager loading everywhere; no N+1 (enable `Model::preventLazyLoading()` outside production).

## 13. Operations
- Backups: spatie/laravel-backup, daily at 03:00, database + media, keep the last 7 only, email on failure. Destination Google Drive (credentials pending — configure local disk now, Drive disk ready to switch on).
- Hosting: Hostinger KVM VPS (Ubuntu) with CloudPanel or Coolify, Redis, Supervisor queue worker, cron for the scheduler, Cloudflare in front.
- Tests (Pest feature tests): checkout with each payment method, Paymob webhook HMAC + idempotency, InstaPay reservation expiry, coupons rules, delivery method rules, stock decrement/restore, WhatsApp logging, filters, register/login/lockout, guard separation, guest order linking, reorder.

## 14. Out of scope (even if visible in the design)
Before/After slider · instalments · Fawry · SMS of any kind · couriers/AWB · trade accounts · spec groups · automatic WhatsApp/email to customers (except password reset) · invoice printing · COD fee/max · advanced SEO (schema/merchant feed) · analytics pixels · multiple admin roles · reports.

## 15. Pending (do not block other work)
1. Paymob API flavour (Intention vs legacy): confirm with the owner before Phase 7 payment tasks.
2. Products Excel import/export: waiting for the client's Excel file to define column matching (SKU is the key).
3. Google Drive backup credentials: waiting for the account email.
