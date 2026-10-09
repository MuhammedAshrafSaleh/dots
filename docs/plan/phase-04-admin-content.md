# Phase 4 — Admin: content and settings

**Goal:** Every non-catalog content in CONTENT_MAP.md is editable from the admin.

Rules: read only this file + PROGRESS.md at session start; open refs only when a task needs them; one commit per task; STOP at the end of the phase.

## T4.1 — Page content editor
- APageContent: page switcher (Home, About, Services, Brands, Projects, Blog, Global blocks), collapsible section cards generated from config/page_sections.php, active switch, media fields. Cache flush on save.
- **Design/docs:** admin-kit.jsx: APageContent, APSection, APField; docs/ERD.md page_sections
- **Done when:** Every page_sections key in config has a working form.

## T4.2 — Services
- AServices/AServiceEdit with included items, gallery, FAQ, footer flag, SEO.
- **Design/docs:** admin-kit.jsx: AServices, AServiceEdit
- **Done when:** CRUD tests.

## T4.3 — Projects
- AProjects (Current/Completed tabs) / AProjectEdit with all ERD fields, stage selector, products/services pickers, gallery order. No before/after.
- **Design/docs:** admin-kit.jsx: AProjects, AProjectEdit
- **Done when:** CRUD tests.

## T4.4 — Blog
- ABlog/APostEdit (draft/published/scheduled, featured, products picker, tags), ABlogTax for categories and tags. Scheduled posts become visible at publish time (query-based, no cron dependency).
- **Design/docs:** admin-kit.jsx: ABlog, APostEdit, ABlogTax
- **Done when:** Tests: scheduled post hidden before date.

## T4.5 — Content pages
- AContentPages/AContentPageEdit for delivery, returns, warranty, careers, terms, privacy (+ new pages), footer flag, last updated.
- **Design/docs:** admin-kit.jsx: AContentPages, AContentPageEdit
- **Done when:** CRUD tests.

## T4.6 — Settings and users
- ASettings tabs: Contact, Commerce (VAT, notes, low-stock default, trust items, product delivery/returns text), WhatsApp templates (variables + live preview), Footer, SEO defaults.
- AUsers: add admin (invite link by email or temp password), deactivate, reset password.
- **Design/docs:** admin-kit.jsx: ASettings*, AUsers
- **Done when:** Tests: settings cache bust; deactivated admin cannot log in.

## T4.7 — Rich text editor: TipTap everywhere (carried from Phase 3)
- Replace the Phase-3 interim `contenteditable` rich-text wrapper (`resources/views/components/admin/rich.blade.php`) with a real TipTap editor in an Alpine wrapper (`wire:ignore`), per Decisions Q6: allowed blocks only — h2, h3, p, bold, italic, link, ul/ol, blockquote, image+caption, callout. Bundle TipTap via the admin Vite entry; keep the server-side `App\Support\Html::clean` (`dots_rich` purifier profile) as the on-save guarantee. Swap it into every rich-text field (services, projects, blog, content pages, page_sections long-text).
- **Done when:** Browser test: type formatted content, save, reload, formatting persists; disallowed HTML is stripped on save. Runs in a real browser (not only component tests).

## T4.8 — Product Files tab: named slots + titled extras (carried from Phase 3)
- Rebuild the product-edit **Files** tab (currently a simple multi-upload) to match the design/ERD: four named slots — Datasheet, IES, LDT, Installation guide — plus titled extra files (bilingual title). Store in the `files` media collection with custom properties `slot`/`type` + `title` per `docs/ERD.md`. Replace/remove per slot; extras are add/remove with a title.
- **Design/docs:** admin-kit.jsx: APFiles, APFileRow; docs/ERD.md product files/media
- **Done when:** CRUD + browser test: upload to each slot and an extra with a title; reload shows them; downloads list on the product page uses slot/title.

## Phase checklist
- [ ] Every CONTENT_MAP admin column points to a working screen
- [ ] TipTap rich text live on every rich-text field; Files tab has the 4 named slots + titled extras
- [ ] Tests green (including browser E2E)
