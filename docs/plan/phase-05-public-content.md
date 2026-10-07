# Phase 5 — Public site: layout and content pages

**Goal:** All non-shop pages are live, driven by the database, in both locales.

Rules: read only this file + PROGRESS.md at session start; open refs only when a task needs them; one commit per task; STOP at the end of the phase.

## T5.1 — Global layout
- Utility strip, header (search with scopes → products/brands), language switch, account icon (state only, accounts come in Phase 8), cart count placeholder, mega menu from the category tree + promo section, projects dropdown, footer (link columns from DB flags, newsletter form, socials, payments line), quote band component.
- Motion from Motion.jsx: preloader (first visit per session), page curtain, custom cursor (desktop), scroll reveal — all disabled under prefers-reduced-motion.
- **Design/docs:** Navbar.jsx, MegaMenu.jsx, Footer.jsx, Shared.jsx, Motion.jsx
- **Done when:** Pixel check against design desktop + 390px, AR + EN.

## T5.2 — WhatsApp click logging
- Route `/{locale}/wa/{source}` with signed params → creates quote_requests row → redirect to wa.me with the rendered template. Blade component `<x-whatsapp-button source=... :model=...>` used everywhere.
- **Design/docs:** PRD §10
- **Done when:** Tests: every source logs a row and redirects; BOQ button never uploads.

## T5.3 — Home
- KHome sections from page_sections + categories (show_on_home) + featured tabs (product flags) + services + brands (show_on_home).
- **Design/docs:** Home.jsx
- **Done when:** No hardcoded strings (grep the view).

## T5.4 — About
- KAbout from page_sections + partners brands.
- **Design/docs:** About.jsx
- **Done when:** Matches design.

## T5.5 — Services and service detail
- KServices, KServiceDetail, consultation form (Livewire, honeypot, rate limit) → consultation_requests, FAQ from faqs.
- **Design/docs:** Services.jsx
- **Done when:** Form test incl. rate limit.

## T5.6 — Projects
- Current (filter by type, featured lead card), Previous (type + year filters, masonry, 8/page), Detail (current vs completed variants, gallery, products/services used, share bar, related).
- **Design/docs:** Projects.jsx (skip BeforeAfter)
- **Done when:** Filters via query string.

## T5.7 — Blog and article
- Featured post, category chips, search, 9/page, newsletter band; article with author block, rich body, tags, sidebar products, related, share.
- **Design/docs:** Blog.jsx
- **Done when:** Newsletter subscribe test (dedupe).

## T5.8 — Brands and brand detail
- Search + A–Z letters, featured brand banner, grid; brand detail with derived category tabs and 'Specified by Dots' projects.
- **Design/docs:** Brands.jsx
- **Done when:** Tabs derived from products only.

## T5.9 — Content pages
- KContentPage template with side nav (pages with show_in_footer) + track order link + WhatsApp help box.
- **Design/docs:** Content.jsx
- **Done when:** Works for all six pages.

## T5.10 — SEO basics
- Meta title/description per page from model SEO or settings defaults, OG tags, hreflang + canonical, sitemap.xml (both locales, scheduled regeneration), robots.txt.
- **Design/docs:** PRD §3
- **Done when:** Validator-clean sitemap; tests for hreflang tags.

## Phase checklist
- [ ] All content pages live in AR and EN
- [ ] No hardcoded content (grep report)
- [ ] WhatsApp logging works on every button
- [ ] Tests green
