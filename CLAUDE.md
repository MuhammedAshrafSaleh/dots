# DOTS — rules for Claude Code

Bilingual (AR RTL default / EN) lighting & electrical catalog + e-commerce. Laravel + Blade + Livewire + Alpine. No Tailwind, no Filament.

## Start of every session (keep token use low)
1. Read `docs/plan/PROGRESS.md` (start with the **Current state** block at the very top — it tells you the branch, current task, last commit, what is left, and what to click) and the ONE phase file it marks as current. Nothing else up front.
2. Open `docs/PRD.md`, `docs/ERD.md`, `docs/CONTENT_MAP.md` only at the section a task points to (use grep / line ranges).
3. Open design files only when the task names them. Never open the original `.html` bundles. **Never read a whole large file** (admin-kit.jsx, styles.css, PRD/ERD/CONTENT_MAP, a big JSX/CSS) — grep for the exact part needed and read only those lines.
4. Do not re-read files you already read in this session.

## Workflow (review-gated; effective from Phase 2)
- **Start of phase N:** from an up-to-date main, create the phase branch **locally** — `git checkout main && git pull && git checkout -b phase-N`. **Do not push it.**
- Work one task at a time, in order. **After each task:** tests pass (`php artisan test`, which includes the real-browser `tests/Browser` E2E, and `npm run build` when front-end changed) → tick the task in PROGRESS.md → **commit locally** `T<N>.<n>: <title>`. **Do not push after tasks.** One local commit per task.
- **A task is NOT done until it works in a real browser.** Component tests (`Livewire::test`) alone are not enough — they bypass the HTTP/middleware cycle. For any task with UI/Livewire: run the browser E2E tests AND open each touched page/action in the real app (dots.test) as the admin, confirming no 403/500/JS error. Only then is the task done.
- **One task per session.** Each task (or a fix round on a task) is its own chat session. The owner opens a new session and says `start T<N>.<n>`, `next task`, or `continue T<N>.<n> — fix: …`. Do exactly that one task, then STOP — do not roll into the next task.
- **Before you stop — whether the task is done or you are mid-fix — update the `Current state` block at the TOP of PROGRESS.md** so a brand-new session can resume from that block alone. It must contain: current branch · current task · last local commit (hash) · open issues / what is left to fix · what the owner should click to review on dots.test. Keep it short and current; overwrite it each time.
- **When a task is done, STOP and tell the owner:** (a) exactly what to click to review it on dots.test, (b) the local commit hash. Fixes the owner requests are new local commits; then STOP again. Stop mid-task only for a real blocker or product decision — ask it as a question.
- Never commit failing tests, `.env`, or any secret. Never force-push. Never rewrite history (no amend, no rebase) on any branch.
- If something in the docs is unclear, contradictory, or needs a product decision: ask, do not guess. Technical choices (package versions, class names) you decide, and log them under "Decisions" in PROGRESS.md.
- Never change the ERD silently. If a column is needed, propose it, then update `docs/ERD.md` after approval.
- **End of phase N:** run the phase checklist, write the "Phase N report" in **`docs/plan/reports/phase-N.md`** (never in PROGRESS.md), **commit it locally**, then **STOP** and tell the owner how to review: URLs to open, commands to run, and `git log --oneline main..phase-N`. Do not merge, tag, or push.
- **Owner review:** the owner reviews the local branch. If they ask for fixes, make them as **new local commits** (no amend, no rebase). Then STOP again for re-review.
- **Only when the owner says "approved, push phase N":** `git push -u origin phase-N`, then `git checkout main && git merge --no-ff phase-N -m "Phase N complete"`, tag `phase-N-done`, and `git push origin main --tags`. **Never push anything without the owner's explicit approval message.**
- If a push fails (network/auth/conflict): **STOP and report the exact error.** Do not work around it, change credentials, or force-push.
- Each 2-line task summary must include the commit hash (local).
- Keep the "Git" line at the top of PROGRESS.md (current branch · last local commit · last tag · push state) updated after every commit.
- **Before every task commit, run all four gates: `php artisan test`, `composer analyse`, `vendor/bin/pint --test`, `npm run build`. A task is NOT done if any gate fails.**

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
- **Browser E2E (`tests/Browser`)** use Pest 4 + Playwright. First time (and in CI): `npx playwright install chromium`. The suite runs the app in-process over a real browser; it uses the `DatabaseTruncation` trait (committed data), not `RefreshDatabase`.
- The in-process browser server keeps the resolved auth guard between requests, so it can NOT reproduce the per-request guard reset that caused the admin `/livewire-*/update` 403. That specific regression is covered by (a) a test asserting `AdminAuth` is registered as Livewire persistent middleware, and (b) manual verification against the real FPM-served app (dots.test).
