# QA Ledger — Phase 8: assets, correspondence, document archive, meetings, my notes, calendar

Date: 2026-10-25 · Env: local QA DB · locale `ar`.
Flows tested in the browser: asset → borrow · department → outgoing letter (numbering, view, PDF) · folder → upload a PDF (DOC-2026-0001) · meeting → view · meeting calendar · note (pinned). Sweep of all 23 module pages: no errors.

| ID | Sev | Finding | Status |
|----|-----|---------|--------|
| P8-01 | U | After borrowing an asset the page still showed status "متاح" (database was right). | **fixed** — record refreshed after the action |
| P8-02 | F | "إرسال للموافقة" was shown on letters, minutes, invoices… even when the approval flow is switched off (default), and pressing it ended in a raw 404 page. | **fixed** — shown only when an active flow exists for the model; test |
| P8-03 | U | Correspondence departments were titled "الأقسام / قسم" on their pages but "الدوائر" in the menu and forms (label key shared with the HR departments resource). | **fixed** — module wording "الدوائر" everywhere |
| P8-04 | U | Manufacturing operations (routing steps) were labelled "عملية مخزنية". | **fixed** — "عملية تصنيع" |
| P8-05 | note | Letter PDF, meeting-minutes PDF render correctly in Arabic RTL; empty sections of the minutes print their heading with no body. | note |
| P8-06 | note | Letter "received at" is stored in UTC, so the PDF date can differ by a day late in the evening until `APP_TIMEZONE=Asia/Muscat` is set (final phase). | open → final phase |

## Known limitations / follow-ups
- Uploading a document does not pre-fill the name from the file name.
- Document-archive resource pages (folders, files) have no module top bar, unlike the other modules.
