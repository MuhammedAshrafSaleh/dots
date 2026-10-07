# Phase 0 — Review (no code)

**Goal:** Read the docs against the design and report problems before anything is built.

Rules: read only this file + PROGRESS.md at session start; open refs only when a task needs them; one commit per task; STOP at the end of the phase.

## T0.1 — Read and cross-check
- Read CLAUDE.md, docs/PRD.md fully, docs/ERD.md fully, design/README.md.
- Skim docs/CONTENT_MAP.md and spot-check 10 rows against the design JSX (grep the strings).
- Check every ERD table is used by at least one phase task in docs/plan.
- **Design/docs:** docs/*, design/README.md
- **Done when:** You can explain the data model and the page list without reopening the docs.

## T0.2 — Write the review report
- Create docs/plan/REVIEW.md with: (a) contradictions or gaps between PRD/ERD/CONTENT_MAP/design, (b) technical risks, (c) package choices you propose (Laravel/Livewire versions, rich-text editor + HTML sanitizer, localization approach for /{locale} + translated slugs, slug generation for Arabic), (d) questions for the owner.
- Do NOT change any doc yet.
- **Design/docs:** docs/plan/REVIEW.md
- **Done when:** REVIEW.md exists. STOP and wait for the owner's answers. Apply approved doc changes, then mark Phase 0 done.

## Phase checklist
- [ ] REVIEW.md written
- [ ] Owner answered the questions
- [ ] Approved changes applied to docs
