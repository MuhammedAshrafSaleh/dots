# Phase 9 — Performance and operations

**Goal:** Fast, safe and recoverable in production.

Rules: read only this file + PROGRESS.md at session start; open refs only when a task needs them; one commit per task; STOP at the end of the phase.

## T9.1 — Query and cache review
- Run EXPLAIN on listing, facets, search, category, brand, home with LargeCatalogSeeder; add/adjust composite indexes (propose ERD change first). Check cache tags and invalidation paths; no N+1 (Debugbar/Telescope locally).
- **Design/docs:** PRD §12
- **Done when:** Report in PROGRESS.md with before/after timings.

## T9.2 — Images
- Media conversions (WebP thumb/card/large/hero), responsive srcset component, lazy loading, queue conversions.
- **Done when:** Lighthouse image audit clean.

## T9.3 — Backups and scheduler
- spatie/laravel-backup daily 03:00 (DB + media), keep last 7, failure email; Google Drive disk wired but off until credentials arrive (local disk active). Scheduler: backups, sitemap, InstaPay reservations, cleanup.
- **Design/docs:** PRD §13
- **Done when:** `backup:run` works locally; retention verified.

## T9.4 — Security and errors
- Security headers, CSRF, rate limits on all public forms/routes, HTML sanitizer on rich text, signed WhatsApp redirect params, 404/500/419/503 pages in the design style, log channels.
- **Done when:** Tests for rate limits.

## Phase checklist
- [ ] Performance report written
- [ ] Backups verified
- [ ] Security checklist done
