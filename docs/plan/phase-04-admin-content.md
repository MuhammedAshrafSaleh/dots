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

## Phase checklist
- [ ] Every CONTENT_MAP admin column points to a working screen
- [ ] Tests green
