# DOTS — rules for Claude Code

Bilingual (AR RTL default / EN) lighting & electrical catalog + e-commerce. Laravel + Blade + Livewire + Alpine. No Tailwind, no Filament.

## Start of every session (keep token use low)
1. Read `docs/plan/PROGRESS.md` and the ONE phase file it marks as current. Nothing else up front.
2. Open `docs/PRD.md`, `docs/ERD.md`, `docs/CONTENT_MAP.md` only at the section a task points to (use grep / line ranges).
3. Open design files only when the task names them. Never open the original `.html` bundles. Never read a whole JSX/CSS file when grep finds the part you need.
4. Do not re-read files you already read in this session.

## Workflow
- Work one task at a time, in order. After each task: run `php artisan test` (and `npm run build` when front-end changed), tick it in PROGRESS.md, commit (`T<phase>.<n>: <title>`), then give a 2-line summary.
- At the end of a phase: run the phase checklist, write a short "Phase N report" at the bottom of PROGRESS.md, and **STOP**. Wait for the owner to say `start phase N+1`.
- If something in the docs is unclear, contradictory, or needs a product decision: ask, do not guess. Technical choices (package versions, class names) you decide, and log them under "Decisions" in PROGRESS.md.
- Never change the ERD silently. If a column is needed, propose it, then update `docs/ERD.md` after approval.

## Non-negotiable rules
- **No hardcoded content in Blade.** Everything in CONTENT_MAP.md comes from the DB. Only UI labels go in `lang/ar|en`.
- All customer-facing text fields are translatable (`{ar, en}`); slugs are per locale.
- No customer file uploads. No SMS. No automatic WhatsApp messages (only prefilled `wa.me` links).
- Paymob credentials only from `.env` (empty placeholders in `.env.example`). Never in DB, admin, code or tests fixtures. Card payment hidden when not configured.
- COD and InstaPay-via-WhatsApp must work with zero Paymob config.
- Snapshot product/variant/price/address data on orders.
- Every admin route/action is behind a named permission (single role "Admin" holds them all).
- Money `decimal(12,2)`, VAT 14% included. Use Egyptian mobile validation `^01[0125]\d{8}$` (strip spaces).
- Respect `prefers-reduced-motion`; RTL first, check LTR.
- Keep files small and conventional: Livewire components under `app/Livewire/{Admin,Shop,Account}`, Blade components under `resources/views/components`, actions/services under `app/Actions` and `app/Services`.

## Commands
`composer test` / `php artisan test` · `npm run dev` / `npm run build` · `php artisan migrate:fresh --seed`
