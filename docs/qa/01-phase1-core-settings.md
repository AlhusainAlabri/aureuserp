# QA Ledger — Phase 1: core & settings

Date: 2026-10-24 · Env: local QA DB (clean install) · locale `ar` · desktop 1100–1440px, Playwright + in-process sweep of 499 admin pages.
Test company: شركة الواحة للتجارة والخدمات ش.م.م (Muscat, OMR, VAT 5%). Test users: `Admin`, `خالد المشتري` (custom role "مدير مشتريات", Purchases = standard).
Severity: **B** blocker · **F** functional · **U** UI · **G** gap · **S** security · **P** polish

## A. First-run & branding
| ID | Sev | Finding | Status |
|----|-----|---------|--------|
| P1-01 | U/G | Fresh install shows another organisation's logo (Zakat Committee, `public/images/logo_2.png`) on login, launcher and PDFs. | **fixed** — neutral default logo (`images/brand/default-*.svg`), override with `BRANDING_DEFAULT_LOGO` / `_COMPACT` in `.env`. **Action for the Zakat site: add `BRANDING_DEFAULT_LOGO=images/logo_2.png` and `BRANDING_DEFAULT_LOGO_COMPACT=images/logo.png` to its server `.env` before deploying.** |
| P1-02 | G | Seeded company is "DummyCorp LLC" with dummy values (reg `DUMREG789`, tax `DUM123456`, `dummycorp.local`, phone `1234567890`, founded 2000, colour `#AAAAAA`). The installer never asks for company name/country/currency. | **fixed** — seeded company is neutral (name from `ERP_COMPANY_NAME`/`APP_NAME`, no dummy values); `erp:install` takes `--company-name/--company-country/--company-currency/--company-email` or prompts |
| P1-03 | F | `Branding::displayName()` is cached 1 h and nothing ever calls `Branding::forgetCache()`: renaming the company leaves the old name in every page title until the cache expires. | **fixed** — cache key versioned; bumped on company save/delete and branding save (tests) |
| P1-04 | G | Only USD is active; OMR exists but is inactive (rounding column is `decimal(8,2)` so OMR's 0.001 is stored as 0.00); company currency select only offers active currencies. `inventory:configure-omr` is a hard-coded "Oman client" script that no install step runs and that deactivates every other currency. | **partly fixed** — installer activates and applies the chosen currency (`--company-currency=OMR`). OMR rounding column precision → Accounting phase |
| P1-05 | G | Oman has no states/governorates (0 rows); country names are English in the Arabic UI. | open → Contacts phase (governorates + Arabic country names) |

## B. Localization (Arabic)
| ID | Sev | Finding | Status |
|----|-----|---------|--------|
| P1-06 | F | `lang/ar/validation.php` has 7 keys, so most validation errors are English/mixed ("The selected العملة الافتراضية is invalid"). | **fixed** — complete `lang/ar` validation/auth/passwords/pagination + parity test |
| P1-07 | U | 23 files hard-code English `$navigationGroup` ('Inventory', 'Sales', 'Accounting', 'Purchase', 'Project', 'Manufacturing', 'Employee', 'Website', 'Invoices'): the Settings sidebar shows English group headings and "Manage Products" on ~32 pages. | **fixed** — 21 settings pages/2 resources use `lang/*/navigation-groups.php` |
| P1-08 | U | 61 pages (41 distinct titles) have English page titles/breadcrumbs because base resources fall back to Filament class-name labels: Companies, Currencies, Email Templates/Logs, Activity Types, Journals, Taxes, Payments, Bills, Invoices, Credit Notes, Refunds, Bank Accounts, Fiscal Positions, Incoterms, Price Lists, Categories, Attributes, Packagings, Locations, Routes, Rules, Lots, Packages, Operation Types, Tags, Milestones, Stages, Activity Plans, Skills, Timesheets, Posts, Pages, Overview… | **fixed** — `TranslatesResourceLabels` trait on 59 resources + `lang/ar/resource-labels.php`; 61 → 0 English titles |
| P1-09 | U | Misc English: "City" field label, company edit "Branches" heading + "إضافة company" button, "Clear selection" on selects, native file inputs show "No file chosen / Choose file" (Branding, user avatar), global search group labels "Accounting→", Approvals nav brand "Approvals". | **mostly fixed** — City label, Branches title/button, global-search group labels. Native file input text is browser-rendered (still open, low) |
| P1-10 | F | Raw translation keys rendered: Time Off leave-types, mandatory-days, employees list filters (`type.table.filters.employee`, `employee.table.filters.birthdate`, `time-off::filament…`, `employees::filament…`). | **fixed** — all 98 keys missing in both languages added (68 lang files) + `TranslationKeysTest` guards literal keys in ar and en |
| P1-11 | U | Role editor: 14 module groups use raw English class names (Blog, Contact, DocumentArchive, Field, Filament, FilamentApproval, Invoice, Manufacturing, Partner, PluginManager, Product, Security, Timesheet, Website); review step headers "Module / Level / Permissions"; "35 / 66" renders as "66 / 35" in RTL; jargon "اسم الحارس" (guard name); role name labelled "العنوان". | **fixed** — module labels, review headers, `35/66` ratio, role name label |
| P1-12 | U | Approval flows list shows model class names (Invoice, PurchaseOrder, AssetBorrowing, PayrollBatch…) and English flow names; seeded flows are duplicated (invoice flow listed twice). Custom fields list shows `CompanyResource`, `EmployeeResource`… | open → Approvals phase (model class names shown; the two invoice flows are one per invoice model) |
| P1-13 | U | User view shows preferred language as raw `ar`. | **fixed** — shows native language name |

## C. UI / RTL
| ID | Sev | Finding | Status |
|----|-----|---------|--------|
| P1-14 | U | Phone numbers render as "96824123456+" in RTL (the `+` lands on the wrong side). Needs `dir="ltr"` on phone/email/URL values. | **fixed** — `LtrValueFormatter` wraps phone/email/URL/code values in an LTR isolate (tests) |
| P1-15 | U | Profile page (`/admin/profile`): avatar and language switcher overlap at top-left and there is no navigation back to the app. | **fixed** — language switcher sits beside the user menu on signed-in simple pages |
| P1-16 | F | `/admin/settings/dashboard-shortcuts/create` → 500 (`DashboardShortcutResource.php:133`, Svg object concatenated as string). | **fixed** — `->toHtml()`; regression test |
| P1-17 | P | Currencies table "التقريب" column shows "$US 0.01" (rounding formatted as money in USD). Backup modal submit is "إرسال". | open → Accounting phase (rounding format); backup button wording low |

## D. Security & access
| ID | Sev | Finding | Status |
|----|-----|---------|--------|
| P1-18 | S | A user with only Purchases access can open `/admin/approval-flows` (+create), `/admin/payroll/employee-components` (salary assignments), `/admin/settings/email-templates`. Other admin pages correctly return 403. | **fixed** — 8 resources had no policy; `ShieldPolicy` registered for them; coverage test fails if any admin resource lacks a policy |
| P1-19 | U/S | Module launcher shows tiles for every module (Payroll, Employees, Settings, Sales…) even when the user has no access; clicking gives 403. | re-verify in browser (was a consequence of P1-18 plus self-service pages) |
| P1-20 | G | Only the `Admin` role ships; no starter roles (HR, accountant, purchasing, sales, warehouse, employee). | **mitigated** — the role wizard already offers role templates (employee, manager, …); seeding concrete starter roles → final phase |

## E. Seeded demo data
| ID | Sev | Finding | Status |
|----|-----|---------|--------|
| P1-21 | G | A fresh install seeds upstream demo data into the customer DB: 10 employees (Paul Williams, John Doe, Jane Smith, Ravi Kumar, …) with partner records and a department manager, 10 job positions, Odoo demo product categories (Home Construction, Office Furniture, Outdoor furniture…), the company itself as customer *and* vendor. Demo employees appear in every "responsible/employee" dropdown. | **fixed** — demo employees/inventory only with `ERP_SEED_DEMO_DATA=true` (default off) |

## F. Verified working
Login/logout, language switch AR↔EN with correct RTL/LTR, global search (Arabic), company edit (OMR after activation), branding display name (immediate), currency activation toggle, role wizard (module access levels none/view/standard/full), user create with custom role, per-role 403 enforcement on most admin pages, system health check (queued via worker), database backup (305-table valid gzip dump), 2FA section present on profile.
