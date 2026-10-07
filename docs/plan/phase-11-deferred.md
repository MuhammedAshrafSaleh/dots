# Phase 11 — Deferred (start only when unblocked)

**Goal:** Waiting on the client.

Rules: read only this file + PROGRESS.md at session start; open refs only when a task needs them; one commit per task; STOP at the end of the phase.

## T11.1 — Products Excel import/export
- When the client's Excel file arrives: define column mapping with the owner, maatwebsite/excel queued import keyed by SKU (create/update products + variants), row-level error report, downloadable template, export of the same columns. Show the Import button in AProducts.
- **Design/docs:** PRD §15
- **Done when:** Import of the client's real file succeeds with an error report.

## T11.2 — Google Drive backups
- When the account email/credentials arrive: configure the Drive filesystem disk and switch backup destination.
- **Design/docs:** PRD §13
- **Done when:** A backup lands in Drive.

## Phase checklist
- [ ] Unblocked items done
