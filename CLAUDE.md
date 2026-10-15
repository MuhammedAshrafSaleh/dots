# DOTS — rules for Claude Code

Bilingual (AR RTL default / EN) lighting & electrical catalog + e-commerce. Laravel + Blade + Livewire + Alpine. No Tailwind, no Filament.

## Start of every session (keep token use low)
1. Read `docs/plan/PROGRESS.md` (start with the **Current state** block at the very top — it tells you the branch, current task, last commit, what is left, and what to click) and the ONE phase file it marks as current. Nothing else up front.
2. Open `docs/PRD.md`, `docs/ERD.md`, `docs/CONTENT_MAP.md` only at the section a task points to (use grep / line ranges).
3. Open design files only when the task names them. Never open the original `.html` bundles. **Never read a whole large file** (admin-kit.jsx, styles.css, PRD/ERD/CONTENT_MAP, a big JSX/CSS) — grep for the exact part needed and read only those lines.
4. Do not re-read files you already read in this session.

## Workflow (review-gated; effective from Phase 2)
- **Start of phase N:** from an up-to-date main, create the phase branch **locally** — `git checkout main && git pull && git checkout -b phase-N`. **Do not push it.**
- Work one task at a time, in order. **After each task:** `composer gates` passes → tick the task in PROGRESS.md → **commit locally** `T<N>.<n>: <title>`. **Do not push after tasks.** One local commit per task.
- **Browser tests are paused.** Do NOT run `composer test:browser` or write new browser tests unless the owner explicitly asks. New behavior must be covered by feature tests (HTTP + Livewire component tests). If a phase plan task calls for browser/E2E tests, write equivalent feature tests and note it in the task's report.
- **When a task touches UI/Livewire:** list exactly what the owner should click on dots.test to verify the task (no 403/500/JS errors). The owner does manual browser review after each task.
- **One task per session.** Each task (or a fix round on a task) is its own chat session. The owner opens a new session and says `start T<N>.<n>`, `next task`, or `continue T<N>.<n> — fix: …`. Do exactly that one task, then STOP — do not roll into the next task.
- **Before you stop — whether the task is done or you are mid-fix — update the `Current state` block at the TOP of PROGRESS.md** so a brand-new session can resume from that block alone. It must contain: current branch · current task · last local commit (hash) · open issues / what is left to fix · what the owner should click to review on dots.test. Keep it short and current; overwrite it each time.
- **When a task is done, STOP and tell the owner:** (a) exactly what to click to review it on dots.test, (b) the local commit hash. Fixes the owner requests are new local commits; then STOP again. Stop mid-task only for a real blocker or product decision — ask it as a question.
- Never commit failing tests, `.env`, or any secret. Never force-push. Never rewrite history (no amend, no rebase) on any branch.
- If something in the docs is unclear, contradictory, or needs a product decision: ask, do not guess. Technical choices (package versions, class names) you decide, and log them under "Decisions" in PROGRESS.md.
- Never change the ERD silently. If a column is needed, propose it, then update `docs/ERD.md` after approval.
- **End of phase N:** run `composer gates` (phase-end check), write the "Phase N report" in **`docs/plan/reports/phase-N.md`** (never in PROGRESS.md), **commit it locally**, then **STOP** and tell the owner how to review: URLs to open, `composer gates` output, and `git log --oneline main..phase-N`. Do not merge, tag, or push. Browser tests are NOT part of the phase-end check.
- **Owner review:** the owner reviews the local branch. If they ask for fixes, make them as **new local commits** (no amend, no rebase). Then STOP again for re-review.
- **Only when the owner says "approved, push phase N":** `git push -u origin phase-N`, then `git checkout main && git merge --no-ff phase-N -m "Phase N complete"`, tag `phase-N-done`, and `git push origin main --tags`. **Never push anything without the owner's explicit approval message.**
- If a push fails (network/auth/conflict): **STOP and report the exact error.** Do not work around it, change credentials, or force-push.
- Each 2-line task summary must include the commit hash (local).
- Keep the "Git" line at the top of PROGRESS.md (current branch · last local commit · last tag · push state) updated after every commit.
- **Before every task commit, run `composer gates` (unit + feature tests + Larastan + Pint + build). A task is NOT done if any gate fails. Browser tests are NOT part of any gate, including the phase-end check, until the owner re-enables them.**

## Database safety (strict)
- Tests must only ever touch dots_testing. A guard in TestCase enforces this. Never remove, weaken or bypass it.
- NEVER run `php artisan config:cache`, `optimize`, `route:cache` or `event:cache` locally. Production only.
- NEVER run migrate:fresh, migrate:reset, db:wipe, db:seed or any destructive command on the `dots` database without my explicit approval.

## Running tests (strict)
- NEVER run more than one test process at a time. Concurrent runs share dots_testing and corrupt each other.
- Before any test run, check `pgrep -fl "artisan test|pest"`. If one is already running, stop and tell me.
- NEVER run tests in the background. If a run is slow, WAIT.
- NEVER pipe test output through tail, head or grep. Use --compact.
- For long runs, write output to a log, then read it when done:
  `php artisan test --parallel --compact > storage/logs/test-run.log 2>&1; cat storage/logs/test-run.log`
- During a task, run only the related tests (--filter=<Name> or --dirty).
- Run the FULL unit+feature suite exactly ONCE per task, right before the commit, via `composer gates`.
- If the suite fails, fix it, re-run only the failing tests with --filter, then run `composer gates` once more.
- Run `composer test:browser` ONLY when the owner explicitly asks.

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
`composer gates` (per-task gate) · `composer test` (unit+feature, parallel) · `composer test:browser` (owner-only) · `npm run dev` / `npm run build` · `php artisan migrate:fresh --seed`
- **Browser E2E (`tests/Browser`)** use Pest 4 + Playwright. Run `composer test:browser` ONLY when the owner explicitly asks. First time (and in CI): `npx playwright install chromium`. The suite uses `DatabaseTruncation` (committed data), not `RefreshDatabase`.
- The in-process browser server keeps the resolved auth guard between requests, so it can NOT reproduce the per-request guard reset that caused the admin `/livewire-*/update` 403. That specific regression is covered by (a) a test asserting `AdminAuth` is registered as Livewire persistent middleware, and (b) manual verification against the real FPM-served app (dots.test).
