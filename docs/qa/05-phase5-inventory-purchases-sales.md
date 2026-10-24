# QA Ledger — Phase 5: inventory, purchases, sales (+ document numbering / PDFs revisited)

Date: 2026-10-25 · Env: local QA DB · locale `ar` · OMR (3 decimals). Flow tested end to end in the browser:
RFQ → confirm → receipt (WH/IN) validate → vendor bill → post; quotation → confirm → delivery (WH/OUT) validate → invoice; stock adjustment.
Severity: **B** blocker · **F** functional · **U** UI · **G** gap · **S** security · **P** polish

| ID | Sev | Finding | Status |
|----|-----|---------|--------|
| P5-01 | B | Printed documents in Arabic showed **wrong amounts**: the glyph shaper reversed numbers next to Arabic ("2.350 ر.ع." printed as "350.2"), dates and codes. | **fixed** — `ArabicText` re-written: words are shaped one by one and laid out right to left, numbers/Latin runs keep their order; whole text nodes instead of fragments (tests) |
| P5-02 | F | Invoice/bill/credit-note/refund, RFQ/PO, quotation/order, delivery & packing slip PDFs: English, LTR, header wrapped in the wrong order, totals pushed to a second page, no taxes/line totals, `-0.000` discount rows. | **fixed** — one shared layout (`pdf.trade-document`) fed by `AccountMoveDocument`, `PurchaseOrderDocument`, `SalesOrderDocument`, `OperationDocument`; mirrored for RTL, 1 page for a normal document, OMR decimals |
| P5-03 | F | Cached RFQ/PO PDFs were reused forever (name only): editing the order and re-printing sent the old PDF. | **fixed** — file name carries last-change time and language |
| P5-04 | F | Documents were numbered with the record id: one counter for every journal/operation type (INV/2026/1, PBANK/2026/2, BILL/2026/3; WH/IN/2 for the first receipt). | **fixed** — `DocumentSequence`: per journal and year (`INV/2026/00002`), per operation type (`WH/IN/00001`); locked; numbers never change after being issued; payment entry shares the payment's number |
| P5-05 | F | A bill created from a purchase order was posted **without a number** (journal looked up while saving, relation cached empty). | **fixed** (`computeJournalId` drops the cached relation; number assigned on save if still empty; test) |
| P5-06 | F | Purchase order lines took the product's **customer** taxes instead of its supplier taxes. | **fixed** |
| P5-07 | G | Default sale/purchase taxes in the accounting settings were never applied; new products had no taxes; fiscal country stayed "United States" for an Omani company. | **fixed** — new products pre-select the default taxes; installer aligns the fiscal country; tests |
| P5-08 | F | Confirming a PO did not link the receipt to the order (receipts tab empty, receipt status never updated). | **fixed** — receipt attached on creation, status recomputed |
| P5-09 | F | `PurchasePaymentService::syncAmounts` re-fired the order's own saved event forever (memory exhausted for any freshly created order). | **fixed** (`updateQuietly`) |
| P5-10 | F | Replenishment/internal requests without a vendor failed (`partner_id` null): the "Misc Supplier" partner is seeded before the company exists on a clean install, so it never existed. | **fixed** — created on first use |
| P5-11 | F | Create-quotation form opened with a validation error shown ("المورد مطلوب") because the currency default read the validated state. | **fixed** (test) |
| P5-12 | F | Confirm on a draft bill/invoice checked the stored record, not the form: filling the date and pressing Confirm gave "invalid date". | **fixed** — saves the form first |
| P5-13 | G | Order view pages had no workflow buttons (confirm, create bill, validate receipt…) — they only existed on the edit page; the PO header overflowed the screen with 11 buttons. | **fixed** — actions on view pages (`updateForm`), secondary ones grouped under "المزيد" |
| P5-14 | U | Breadcrumbs/titles with a stray English "s" ("التسليماتs", 26 resources); English model labels in 25 base resources; relation pages titled "Manage PO/1 Operations". | **fixed** — `TranslatesResourceLabels` on all 129 resources (strips the plural s, uses `lang/ar/resource-labels.php`), `TranslatesRelationPageTitle` on 57 relation pages; tests |
| P5-15 | U | English leftovers: "Untaxed Amount/Tax/Total" summaries, section/tab titles (Tax Mapping…), "State/Narration/Due terms/…", relation tabs (Bank Accounts…), "Moves", "days". | **fixed** — 80+ ar.json keys, literal titles wrapped |
| P5-16 | F | Line amount inputs showed 4 decimals (28.5000) or none (18) for OMR. | **fixed** — currency decimals when computed and when loaded |
| P5-17 | U | Quotation form defaulted payment term to a hard-coded id 10 ("90 days, on the 10th") and wiped it when the customer had none. | **fixed** |
| P5-18 | G | Country names, currency names in English ("Oman", "United States dollar"); Oman had no governorates. | **fixed** — names from ICU in `erp:localize-data`, 11 governorates added (tests) |
| P5-19 | U | Upstream demo account tags ("Demo CEO Wages Account"…) installed on every site. | **fixed** — only with `ERP_SEED_DEMO_DATA` |
| P5-20 | S/F | Chatter/log inserts failed with `causer_type` null when a change happened without a signed-in user (queue, API tests); role *view* page returned 500. | **fixed** |
| P5-21 | F | Dashboards `/admin/project`, `/admin/recruitment`, time-off "by type" returned 500 (widgets without a Shield permission). | **fixed** (done in Phase 4 commit) |

## Known limitations / follow-ups
- Long Arabic paragraphs inside PDFs (terms, notes) still wrap in reverse line order: Dompdf cannot lay out right-to-left text, so words are reordered ahead of time and a wrapped line comes out bottom-to-top. Keep PDF text short or switch the engine (needs a dependency decision).
- Wide tables (stock adjustments, order lines) scroll horizontally in RTL; row actions sit at the far end.
- `/admin/email-verification/prompt` still 500s when no verification is configured (low).
- PO/SO/MO numbers remain `PO/<id>` (gap-free unless a record is deleted); change later if customers want `PO/2026/00001`.
