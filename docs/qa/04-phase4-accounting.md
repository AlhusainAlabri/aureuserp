# QA Ledger — Phase 4: accounting, invoices, payments

Date: 2026-10-25 · Env: local QA DB · locale `ar` · company OMR (3 decimals), VAT 5%.
Severity: **B** blocker · **F** functional · **U** UI · **G** gap · **S** security · **P** polish

| ID | Sev | Finding | Status |
|----|-----|---------|--------|
| P4-01 | B | Posting an OMR invoice crashed: `precision_rounding must be positive, got 0.00` (currency `rounding` column was `decimal(8,2)`, so 0.001 stored as 0.00). | **fixed** — precision migration, `Currency::getRoundingAttribute`, tests |
| P4-02 | B | Invoice confirm crashed (`guardApprovalBeforePosting` was protected). Seeded approval flows were active and blocked posting with no approver. | **fixed** — guard public; seeded flows inactive by default |
| P4-03 | F | Invoice currency defaulted to null (wrong table name for the company currency lookup). | **fixed** — `DefaultCurrency::id()` |
| P4-04 | F | Amounts rounded to 2 decimals in invoices, bills, quotations, orders, journal entries and move lines. | **fixed** — `money_round()` honours currency precision |
| P4-05 | U | `(Deleted)` suffix and pay-action labels in English; payment form did not refresh state after paying. | **fixed** — 31 files translated, `PayAction` refreshes `state`/`payment_state` |
| P4-06 | U | Account tag page rejected valid input (numeric/max value rules). | **fixed** |
| P4-07 | G | Default tax profile (VAT 5%) was not applied for an Omani company install. | **fixed** — `DefaultTaxProfile`, applied by `erp:localize-data` |
| P4-08 | F | Document numbers were `prefix/<record id>`: one counter shared by all journals, so numbers skipped (INV/2026/1, PBANK/2026/2, BILL/2026/3). | **fixed** — `DocumentSequence`: per journal and year, `INV/2026/00001`; payment journal entry reuses the payment number; locked; tests |
| P4-09 | F | Dashboards `/admin/project`, `/admin/recruitment` and time-off "by type" report returned 500 (`canAccess does not exist`): app widgets extending plugin widgets are not discovered by Shield, whose `canView()` then calls a method widgets do not have. | **fixed** — `InheritsWidgetShieldPermission` on 6 widgets + test over all app widgets |

Still open (low): `/admin/email-verification/prompt` 500 when no verification is configured.
Existing QA documents keep their old numbers (INV/2026/1); only new documents use the new format.
