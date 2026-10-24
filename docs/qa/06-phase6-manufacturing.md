# QA Ledger — Phase 6: manufacturing

Date: 2026-10-25 · Env: local QA DB · locale `ar`. Optional modules were switched on to test them (work orders, byproducts, lots, packages, locations, units of measure, purchase agreements).
Flow tested in the browser: work center → BOM with components and an operation → manufacturing order → confirm → start → produce all (with and without work orders) → print.

| ID | Sev | Finding | Status |
|----|-----|---------|--------|
| P6-01 | U | Quantity + unit of measure fields squeezed the unit select to ~90px ("وحد ات" wrapped letter by letter) on the MO and BOM forms; placeholder "UoM" in English. | **fixed** — wider unit column, BOM group takes the full row |
| P6-02 | F | Manufacturing order print was English, LTR, with raw barcodes. | **fixed** — shared document layout (`ManufacturingOrderDocument`): components and operations tables; test |
| P6-03 | U | Workflow buttons (confirm, start, produce…) left the page with a browser "leave site?" warning although nothing was edited (Filament's unsaved-changes alert compares against the state from before the button). | **fixed** — `KeepUnsavedChangesAlertAccurate` Livewire hook refreshes the saved state after such actions; real edits still warn (checked) |
| P6-04 | U | Infolist/table entries named `uom.name`, `product.name`… were all labelled "الاسم" (BOM view: unit, product…). | **fixed** — default label comes from the relation ("وحدة القياس", "المنتج") |
| P6-05 | U | Relation tab groups in English (Bank Accounts, Contacts, Addresses, Sub Tasks, Capacity By Products…). | **fixed** |
| P6-06 | U | MO view showed 3.0000 / 3.0000 quantities. | **fixed** (trailing zeros trimmed) — other quantity columns still show 4 decimals (see below) |
| P6-07 | G | Work orders, byproducts, lots/serials, packages, locations, purchase agreements are off by default, so their pages answer 404 until enabled in Settings (works as designed; sweep re-run with all enabled: no new errors). | note |

## Known limitations / follow-ups
- Quantities (stock, BOM, moves) display four decimals ("10.0000"); a per-unit rounding display would read better.
- Date pickers in a few manufacturing forms show `25/10/2026, 12:21 am` (English am/pm) instead of the app's Arabic format.
- Console shows a harmless Alpine "column is not defined" message after producing (column-manager popover re-render).
