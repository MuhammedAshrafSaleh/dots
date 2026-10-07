# DOTS — rules for Claude Code

Bilingual (AR RTL default / EN) lighting & electrical catalog + e-commerce. Laravel + Blade + Livewire + Alpine. No Tailwind, no Filament.

## Start of every session (keep token use low)
1. Read `docs/plan/PROGRESS.md` and the ONE phase file it marks as current. Nothing else up front.
2. Open `docs/PRD.md`, `docs/ERD.md`, `docs/CONTENT_MAP.md` only at the section a task points to (use grep / line ranges).
3. Open design files only when the task names them. Never open the original `.html` bundles. Never read a whole JSX/CSS file when grep finds the part you need.
4. Do not re-read files you already read in this session.

## Workflow (review-gated; effective from Phase 2)
- **Start of phase N:** from an up-to-date main, create the phase branch **locally** — `git checkout main && git pull && git checkout -b phase-N`. **Do not push it.**
- Work one task at a time, in order. **After each task:** tests pass (`php artisan test`, and `npm run build` when front-end changed) → tick the task in PROGRESS.md → **commit locally** `T<N>.<n>: <title>`. **Do not push after tasks.** One local commit per task.
- Never commit failing tests, `.env`, or any secret. Never force-push. Never rewrite history (no amend, no rebase) on any branch.
- If something in the docs is unclear, contradictory, or needs a product decision: ask, do not guess. Technical choices (package versions, class names) you decide, and log them under "Decisions" in PROGRESS.md.
- Never change the ERD silently. If a column is needed, propose it, then update `docs/ERD.md` after approval.
- **End of phase N:** run the phase checklist, write the "Phase N report" in PROGRESS.md, **commit it locally**, then **STOP** and tell the owner how to review: URLs to open, commands to run, and `git log --oneline main..phase-N`. Do not merge, tag, or push.
- **Owner review:** the owner reviews the local branch. If they ask for fixes, make them as **new local commits** (no amend, no rebase). Then STOP again for re-review.
- **Only when the owner says "approved, push phase N":** `git push -u origin phase-N`, then `git checkout main && git merge --no-ff phase-N -m "Phase N complete"`, tag `phase-N-done`, and `git push origin main --tags`. **Never push anything without the owner's explicit approval message.**
- If a push fails (network/auth/conflict): **STOP and report the exact error.** Do not work around it, change credentials, or force-push.
- Each 2-line task summary must include the commit hash (local).
- Keep the "Git" line at the top of PROGRESS.md (current branch · last local commit · last tag · push state) updated after every commit.

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
