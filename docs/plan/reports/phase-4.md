# Phase 4 Report — Admin: content and settings

**Branch:** `phase-4` (local, not pushed)  
**Last commit:** `0ca3fc2` T4.8: Product Files tab: named slots + titled extras  
**Date:** 2026-10-13

---

## Gate results

| Gate | Result |
|---|---|
| `php artisan test` | ✅ 223 tests, 223 passed, 805 assertions |
| `npm run build` | ✅ Clean build (admin 388 kB JS, 107 kB CSS) |
| `composer analyse` (PHPStan) | ❌ 8 errors in 3 files — see Failures |
| `vendor/bin/pint --test` | ❌ 14 files need formatting — see Failures |

---

## Phase checklist

- ✅ **Every CONTENT_MAP admin column points to a working screen.** All Phase 4 admin columns verified: page_sections → `admin/page-content/{page}`, services → `admin/services`, projects → `admin/projects`, blog/posts/categories → `admin/blog` + `admin/blog/taxonomy`, content pages → `admin/content-pages`, settings (contact/footer/seo/commerce/whatsapp) → `admin/settings`, users → `admin/users`.
- ⚠️ **TipTap rich text live on every rich-text field; Files tab has the 4 named slots + titled extras.** TipTap (`x-admin.rich`) confirmed on: blog body, project about, content-page body, product overview, page_sections long-text fields. **`services.description` uses a plain multiline `x-admin.field-bilingual` instead of TipTap** — T4.7 spec listed services as a rich-text target. Files tab has the 4 named slots (datasheet, ies, ldt, guide) plus bilingual-titled extras.
- ✅ **Tests green (including browser E2E).** All 223 tests pass including Browser suite.

---

## CONTENT_MAP Phase 4 coverage

| Content area | CONTENT_MAP key | Admin screen | Status |
|---|---|---|---|
| Top strip | `settings contact.*` | Settings › Contact | ✅ |
| Footer about/copyright/payments | `settings footer.*` | Settings › Footer | ✅ |
| Social links | `settings contact.social_*` | Settings › Contact | ✅ |
| SEO defaults | `settings seo.default_title` | Settings › SEO | ✅ |
| Commerce (VAT, trust, delivery/returns) | `settings commerce.*` | Settings › Commerce | ✅ |
| WhatsApp templates | `settings whatsapp.tpl_*` | Settings › WhatsApp | ✅ |
| Services (list + single) | `services.*` / `service_items.*` | admin/services | ✅ |
| Page sections — all pages | `page_sections *.*.field` | admin/page-content/{page} | ✅ |
| Projects (current + completed) | `projects.*` | admin/projects | ✅ |
| Blog posts + categories + tags | `posts.*` / `post_categories.*` | admin/blog + blog/taxonomy | ✅ |
| Content pages (delivery, returns, etc.) | `content_pages.*` | admin/content-pages | ✅ |
| Footer services column | `services.show_in_footer` | Services edit form | ✅ |
| Product overview | `products.overview` (rich) | Product edit › Overview tab | ✅ |
| Product files | `media[files]` slot/title | Product edit › Files tab | ✅ |

---

## Route permissions

Every Phase 4 admin route sits behind `admin.auth` middleware + a named `can:` permission:

- `admin/page-content` → `can:page_content.view`
- `admin/services` → `can:services.view/create/update`
- `admin/projects` → `can:projects.view/create/update`
- `admin/blog` → `can:posts.view/create/update`
- `admin/blog/taxonomy` → `can:post_categories.manage`
- `admin/content-pages` → `can:content_pages.view/create/update`
- `admin/settings` → `can:settings.view`
- `admin/users` → `can:users.view`

All ✅.

---

## Failures — must fix before push

### 1. PHPStan — 8 errors

**`app/Livewire/Admin/Blog/Edit.php`**
- Line 91: `Cannot access property $value on string` (`property.nonObject`)
- Line 92: `Cannot call method format() on string` (`method.nonObject`)

**`app/Livewire/Admin/Projects/Edit.php`**
- Lines 115–120: Same `$value` access / `format()` on string (5 errors)

**`app/Livewire/Admin/Products/Edit.php`**
- Line 743: `Unable to resolve template type TNewKey` in `Collection::keyBy()`

### 2. Pint — 14 files need formatting

`app/Livewire/Admin/Settings/Edit.php`, `Products/Edit.php`, `Projects/Edit.php`, `PageContent/Edit.php`, `Blog/Edit.php`, `Users/Index.php`, `Services/Edit.php`, `tests/Browser/AdminFlowTest.php`, `tests/Feature/Admin/Products/ProductEditTest.php`, `tests/Feature/Admin/PageContent/PageContentTest.php`, `tests/Feature/Admin/Services/ServicesCrudTest.php`, `lang/ar/admin.php`, `lang/en/admin.php`, `routes/admin.php`.

### 3. Uncommitted working-tree changes

`resources/js/admin.js` and `tests/Browser/AdminFlowTest.php` have unstaged edits not included in any commit.

### 4. `services.description` not on TipTap

T4.7 spec: "Swap it into every rich-text field (services, …)". The service edit form uses `x-admin.field-bilingual multiline` for `description` instead of `x-admin.rich`. Confirm with owner whether this was intentional.

---

## 10-minute smoke-test list

| Screen | URL | Action |
|---|---|---|
| Page content — Home | `http://dots.test/admin/page-content/home` | Edit Hero title (both locales), save, reload — text persists |
| Page content — Services | `http://dots.test/admin/page-content/services` | Edit "How we work" step title, save, reload |
| Services list | `http://dots.test/admin/services` | Confirm list renders with sort handles |
| Service edit | `http://dots.test/admin/services/1/edit` | Add an FAQ item, save, reload — FAQ row persists |
| Projects list | `http://dots.test/admin/projects` | Confirm Current / Completed tabs switch |
| Project edit | `http://dots.test/admin/projects/1/edit` | Change stage, save, reload |
| Blog list | `http://dots.test/admin/blog` | Filter by status=draft |
| Blog post edit | `http://dots.test/admin/blog/create` | Type in TipTap body (both locales), save, reload — bold/heading persists |
| Blog taxonomy | `http://dots.test/admin/blog/taxonomy` | Add a tag, delete a category |
| Content pages | `http://dots.test/admin/content-pages` | Edit Delivery page body, save, reload |
| Settings | `http://dots.test/admin/settings` | Update hotline (Contact tab), switch to Commerce tab, change VAT note, save |
| Users | `http://dots.test/admin/users` | Deactivate a non-self admin, confirm cannot log in |
| Product Files | `http://dots.test/admin/products/1/edit` → Files tab | Upload Datasheet, save, reload — filename shows; add extra file with bilingual title |
