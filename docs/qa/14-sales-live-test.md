# QA Ledger — Sales live test (quotation → delivery → invoice → payment → return)

Date: 2026-10-27 · Env: local QA DB, locale `ar`, dark theme, OMR (3 decimals). Flow run in the browser: quotation (2 lines, 5% VAT) → confirm → delivery with backorder → invoice → partial payment → return → new customer.
Severity: **B** blocker · **F** functional · **U** UI · **G** gap · **P** polish

| ID | Sev | Finding | Status |
|----|-----|---------|--------|
| SL-01 | B | Validating a delivery of 20 with 10 on hand silently took stock to -10 (`checkQuantity` was an empty stub). | **fixed** — deliveries and internal transfers refuse to validate when the source location holds less than the lines take; Arabic message; tests |
| SL-02 | F | Returns never lowered the order's delivered quantity, so no credit note was possible: the Return dialog toggle showed for purchases only, and the sales code compared a location model to an enum. | **fixed** — toggle "update delivered quantities on the sales order" on sales deliveries (default on); enum compare fixed; tests |
| SL-03 | F | The invoice contained a zero-quantity line for an undelivered product. | **fixed** — lines with nothing to invoice are skipped; test |
| SL-04 | F | The order showed "invoiced" while one line was still undelivered (`contains` instead of "all lines"). | **fixed** — invoiced / upselling need every line; test |
| SL-05 | F | Invoicing the same line twice detached the first invoice (`sync`), which would double-invoice partial deliveries. | **fixed** — `syncWithoutDetaching`; test |
| SL-06 | F | A lead time stored as a decimal string crashed Carbon in the line expected date, and the call changed the order date. | **fixed** — integer cast, date copied |
| SL-07 | F | The customer list offered your own company and "Admin". | **fixed** — excluded, same as the purchase vendor list; test |
| SL-08 | G | The edit page had no expiry date (hidden for every record). | **fixed** — shown for draft/sent, hidden once confirmed; test |
| SL-09 | U | Dark theme: the preview showed dark-grey text on a dark background, and its CSS (`body`, `table`, `td`) leaked onto the page. | **fixed** — `DocumentPreview` scopes the CSS and puts the document on a white sheet; used by the quotation and invoice/bill/credit-note previews; tests |
| SL-10 | U | "My Quotations" (default tab) listed confirmed orders; the list footer showed the total twice. | **fixed** — tab limited to draft/sent; existing summarizers relabelled, not duplicated; tests |
| SL-11 | U | English text "No quantities are reserved" in the Arabic validate messages. | **fixed** |
| SL-12 | F | SO/2 reserved 20 against 10 on hand during the live session. Not reproduced in a rolled-back replay of the same data, nor in tests (reserves 10, partially assigned). | **open** — SL-01 stops the damage at validation; report the steps if it recurs |
| SL-13 | U | The tax chip is truncated ("ضر…") in the line table and invoice header buttons overflow at ~800px. | **open** — check at desktop width |
| SL-14 | U | Create-customer page title is just "العملاء"; the create-quotation summary showed a stale total once (may be screenshot lag); the create-invoice dialog repeats its title. | **open** |
| SL-15 | P | After creating a quotation the app opens the edit page, not the view page; line `sort` starts at 2; invoice view shows quantity as 20.0000. | **open** |

## Enhancements suggested
- Validate phone numbers (junk was accepted); default the customer country to Oman.
- Allow invoicing a confirmed order before delivery (only "invoice on delivered quantity" exists).
- "Send by email" and a PDF/print button on the confirmed-order header.
- Create the credit note straight from a return.

## Known unrelated failure
`tests/Unit/ExtensionSchemaMigrationsTest` fails: `2026_10_29_100000_shift_default_calendar_weekend_to_friday_saturday` (HR commit `41ece226e`) is missing from `config/extension-schema.php`.
