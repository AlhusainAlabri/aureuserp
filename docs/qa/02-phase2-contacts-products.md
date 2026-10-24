# QA Ledger — Phase 2: contacts, partners, products, reference data

Env: QA DB, locale `ar`. Flows run in the UI: create company contact, create product, product list/view, currency settings. Customers/vendors/categories/products bulk-created with the real models.

| ID | Sev | Finding | Status |
|----|-----|---------|--------|
| P2-01 | F | Every `->money()` column/entry showed US dollars with 2 decimals (`$US 2.35`) because Filament's default currency is `usd` and nothing overrode it; the global default-currency setting and the company currency were ignored. | **fixed** — `DefaultCurrency` follows Settings > Currency (falls back to `APP_CURRENCY`); wired into tables, infolists and `money()`; scoped per request/job. Price now `2.350 ر.ع.` |
| P2-02 | U | Product form: unit-of-measure select squeezed in the pricing sidebar ("وحدا / ت" wrapped letter by letter). | **fixed** — 2-column fused group, no clear button |
| P2-03 | U | Product form inner fields had default English labels ("Price", "Cost", "Uom id", "Uom po id", "Images", "Product taxes", "Supplier taxes") and tax helper text was English ("Incl. Taxes"). | **fixed** |
| P2-04 | G | ~455 seeded reference values were English (chart of accounts, journals, payment terms, incoterms, units, stock operation types/locations/routes/rules, job positions, skills, recruitment stages, leave types, UTM, website pages…). | **fixed** — `erp:localize-data` + `lang/ar/seed-data.php`; runs after each plugin install when the locale is not English; only touches rows still holding the seeded English value (tests) |
| P2-05 | F | 81 `__('English sentence')` keys had no Arabic entry in `lang/ar.json` (accounting/inventory/manufacturing validation messages, weekday names, payment tabs, recruitment states) and ~46 labels were hard-coded English. | **fixed** — all translated; `TranslationKeysTest` now fails if a new one is missing |
| P2-06 | G | Two "default currency" settings (Settings > Currency and the company) are independent. | **mitigated** — installer sets both from `--company-currency`; the helper reads the settings one |
| P2-07 | P | Seeded warehouse is named "Your Company". | **fixed** — localized to "المستودع الرئيسي" for Arabic installs |

Verified working: contact create (company type, Arabic data, phones render LTR-correct), contact view, product create/view/list, customers list (card layout), file uploader (Arabic), price/cost with 3 decimals.
Not yet covered here (later phases): governorates for Oman, 3-decimal rounding for OMR in accounting, tax set-up (Tax 15% group / "CESS 5%" seeded demo taxes).
