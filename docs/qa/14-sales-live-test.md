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
| SL-14 | U | Create-customer page title was just "العملاء"; the create-quotation summary showed a stale total once (may be screenshot lag); the create-invoice dialog repeats its title. | **partly fixed** — title is "إضافة عميل"; the other two stay open |
| SL-15 | P | After creating a quotation the app opens the edit page, not the view page; line `sort` starts at 2 (global max, invisible); invoice/bill view showed quantity as 20.0000. | **partly fixed** — quantity shows 20; the other two are by design / cosmetic |
| SL-16 | B | Cancelling any confirmed sales order crashed: the sales code called `Inventory::cancelOperation()`, which does not exist (the method is `cancelTransfer`). | **fixed** — open deliveries are cancelled and their reservation freed; tests |
| SL-17 | F | Cancel had no guards: an order with a posted invoice or delivered goods could be cancelled. | **fixed** — refused with an Arabic message (credit note / return first); draft invoices are cancelled with the order; the plain Cancel button shows the message instead of an error; tests |
| SL-18 | B | With "lock confirmed orders" on, confirming created **no delivery** (the lock was set before the delivery step, which skips locked orders). | **fixed** — the lock is applied last; test |
| SL-19 | F | Cancelling with "send email" crashed when the customer had no email address. | **fixed** — customers without an email are skipped; test |
| SL-20 | G | No salesperson role: only Admin, finance_manager and general_manager held sales permissions. | **fixed** — `sales_manager` starter role (Sales and Contacts standard; Inventory and Invoices view-only), Arabic label; tests. Record scope is per user (`individual` by default); set `group`/`global` on the user for a sales manager. |
| SL-21 | G | Sales teams could not be assigned to orders (no field); the salesperson field showed empty and was cleared by choosing a customer without a salesperson; a team's company was not defaulted. | **fixed** — team select on the quotation, salesperson defaults to the signed-in user and is kept, team company defaults; tests |
| SL-22 | F | The authorized-user cache in `Bouncer` was static and not tied to a user, so one user's record scope could leak to another user in the same process (queue workers, tests). | **fixed** — keyed by user |
| SL-23 | note | Quotation templates exist only as page stubs (no resource), so they are not reachable. Optional products are listed on the quotation but never turned into lines. The Standard access level also grants the Sales settings pages and the configuration screens. | **by design / not shipped** |

## Enhancements suggested
- Validate phone numbers (junk was accepted); default the customer country to Oman.
- Allow invoicing a confirmed order before delivery (only "invoice on delivered quantity" exists).
- "Send by email" and a PDF/print button on the confirmed-order header.
- Create the credit note straight from a return.

## Known unrelated failure
`tests/Unit/ExtensionSchemaMigrationsTest` fails: `2026_10_29_100000_shift_default_calendar_weekend_to_friday_saturday` (HR commit `41ece226e`) is missing from `config/extension-schema.php`.
