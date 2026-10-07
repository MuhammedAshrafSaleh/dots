You are building the DOTS website in this folder. Everything you need is already here:

- CLAUDE.md — rules you must follow every session (read it first).
- docs/PRD.md — approved requirements and decisions.
- docs/ERD.md — approved database design (source of truth for migrations).
- docs/CONTENT_MAP.md — every visible field on every page → table/column → admin screen. Nothing on it may be hardcoded.
- design/ — the final website and admin design, already unpacked (see design/README.md). Never look for the original HTML bundles.
- docs/plan/ — the build plan: PROGRESS.md + one file per phase, each split into small tasks with "done when" criteria.

The plan is already written and approved. Do not rewrite it. Your job now is Phase 0 only:

1. Read CLAUDE.md, docs/plan/PROGRESS.md and docs/plan/phase-00-review.md.
2. Do the Phase 0 tasks: cross-check PRD, ERD, CONTENT_MAP and the design, then write docs/plan/REVIEW.md with contradictions/gaps, technical risks, the packages and approaches you propose, and your questions for me.
3. Do not write any application code and do not edit the other docs.
4. STOP after REVIEW.md and wait for my answers.

After I approve, each new session I will say "start phase N" (or "continue phase N" after a /clear). Then you read only CLAUDE.md, PROGRESS.md and that phase file, do the tasks in order, test + tick + commit each task, and stop at the end of the phase.

Local environment: macOS with Laravel Herd. The site is served at http://dots.test from this folder (PHP and Composer come from Herd). MySQL database "dots" and Redis run locally on default ports (root user, no password unless I tell you otherwise). Do not install another PHP or web server.
