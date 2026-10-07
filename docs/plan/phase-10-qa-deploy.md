# Phase 10 — QA and deployment

**Goal:** Ready to ship.

Rules: read only this file + PROGRESS.md at session start; open refs only when a task needs them; one commit per task; STOP at the end of the phase.

## T10.1 — Content coverage report
- Script/test that walks docs/CONTENT_MAP.md and proves every row is DB-driven and has an admin field; grep views for leftover literal Arabic/English content.
- **Design/docs:** docs/CONTENT_MAP.md
- **Done when:** Report shows 100% coverage.

## T10.2 — Full test pass and manual QA
- All Pest tests; manual pass of every page in AR/EN, desktop/390px, logged out/in; keyboard focus; reduced motion.
- **Done when:** QA checklist in PROGRESS.md all ticked.

## T10.3 — Deployment guide
- docs/DEPLOY.md for Hostinger KVM: Ubuntu + CloudPanel/Coolify, PHP extensions, MySQL, Redis, Supervisor queue worker, cron, storage link, env checklist (PAYMOB_* left empty until provided), Cloudflare, backups, first admin user.
- **Done when:** Guide reviewed by owner.

## Phase checklist
- [ ] Coverage 100%
- [ ] QA done
- [ ] Deploy guide ready
