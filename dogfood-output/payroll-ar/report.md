# Payroll Module — Arabic E2E QA Report

**Date:** 2026-06-02  
**Environment:** Local (`http://127.0.0.1:8000/admin`)  
**Locale:** Arabic (`?lang=ar`)  
**Tester:** Browser pass (agent-browser) + HTTP/render validation + Pest tests  
**Credentials:** Admin `nodhumtech@gmail.com` / `Oman@999` (see `auth.md`)

---

## Executive summary

| Metric | Result |
|--------|--------|
| Pages covered | **11 routes** (lists, creates, view/edit sample, reports, settings cluster) |
| Critical bug found & fixed | **P0** — payroll list pages showed **عروض الأسعار** (sales quotations) as title/empty state |
| Arabic navigation & forms | **Strong** after fix |
| Create / view / edit | **Forms Arabic**; seeded **English names** in data (e.g. Basic Salary) |
| Automated tests added | `tests/Feature/PayrollArabicLabelsTest.php` |

---

## Pre-flight

| Check | Status |
|-------|--------|
| Payroll plugin installed | Yes |
| Sample salary components | 5 seeded (BASIC, HOUSING, etc.) |
| Payroll batches / payslips | 0 at test time |

---

## Pages tested

| # | Route | Result | Notes |
|---|-------|--------|-------|
| 1 | `/admin/payroll/payroll-batches` | **Pass** (after fix) | Title **دفعات الرواتب**; columns Arabic; OMR formatting |
| 2 | `/admin/payroll/payroll-batches/create` | **Pass** | Title **إضافة دفعة الرواتب**; period fields Arabic |
| 3 | `/admin/payroll/payslips` | **Pass** (after fix) | Title **كشوف الرواتب**; tabs الكل / مسودة / معتمد / مدفوع |
| 4 | `/admin/payroll/loans` | **Pass** (after fix) | Title **قروض الموظفين** |
| 5 | `/admin/payroll/loans/create` | **Pass** | Title **إضافة قرض الموظف** |
| 6 | `/admin/payroll/employee-components` | **Pass** (after fix) | Title **تعيينات الراتب** |
| 7 | `/admin/payroll/employee-components/create` | **Pass** | Title **إضافة تعيين الراتب** |
| 8 | `/admin/payroll/configuration/salary-components` | **Pass** | Title **مكونات الراتب**; table fully Arabic |
| 9 | `/admin/payroll/configuration/salary-components/create` | **Pass** | Sections **التفاصيل**, **الحساب**, **المحاسبة** |
| 10 | `/admin/payroll/configuration/salary-components/{id}` | **Partial** | Labels Arabic; **record title** uses English DB name in breadcrumb/H1 |
| 11 | `/admin/payroll/configuration/salary-components/{id}/edit` | **Partial** | Same as view — form labels OK, title **تعديل Basic Salary** |
| 12 | `/admin/payroll/reports` | **Pass** | Title **تقارير الرواتب**; filters & stats Arabic; **ر.ع.** amounts |
| 13 | `/admin/payroll/configuration` | **Pass** | Lands on salary components (settings cluster) |

Screenshots: `dogfood-output/payroll-ar/screenshots/` (`fixed-*.png`, `create-*.png`, `view-*`, `edit-*`).

---

## P0 — Fixed during QA

### Wrong list titles (sales quotation label leaked into payroll)

**Symptom:** List pages for payroll batches, payslips, loans, and employee assignments showed:

- Page title & `<title>`: **عروض الأسعار**
- Empty state: **لا توجد عروض الأسعار**
- Table headers were correct (payroll fields)

**Root cause:** [`SalesExtensionsServiceProvider`](app/Providers/SalesExtensionsServiceProvider.php) used reflection to set `Filament\Resources\Resource::$pluralModelLabel` on `QuotationResource`. That property is declared on the **base** `Resource` class and is **shared by all Filament resources**, so every module inherited **عروض الأسعار**.

**Fix applied:**

1. Removed the global reflection hack from `SalesExtensionsServiceProvider`.
2. Added explicit `getPluralModelLabel()` on payroll resources + `models_plural` keys in [`plugins/webkul/payroll/resources/lang/{en,ar}/payroll.php`](plugins/webkul/payroll/resources/lang/ar/payroll.php).
3. Custom empty-state heading via `payroll::payroll.table.empty` on list pages.
4. Sales quotations list title restored via `getTitle()` override on [`app/Filament/Sales/Pages/ListQuotations.php`](app/Filament/Sales/Pages/ListQuotations.php).

**Verification:** HTTP render + browser titles now show **دفعات الرواتب**, **كشوف الرواتب**, etc. Pest: `PayrollArabicLabelsTest`, updated `SalesArabicLabelsTest`.

---

## Findings by priority

### P1 — Translation / UX

| ID | Issue | Notes |
|----|-------|-------|
| **P-P1-1** | **Record titles use English seed data** | View/edit H1 and breadcrumbs: `عرض Basic Salary`, `تعديل Basic Salary`. `name` field is English; `name_ar` is Arabic in infolist. Consider showing `name_ar` when `app()->getLocale() === 'ar'`. |
| **P-P1-2** | **Month field shows digit `6`** on batch create | Label **الشهر** is Arabic; value is numeric, not **يونيو**. |
| **P-P1-3** | **Payment date mixed locale** | Displays **يونيو 25, 2026** (Arabic month + English order). |
| **P-P1-4** | **Filament “Search” placeholder** | Relationship selects (e.g. journal) likely still show English **Search** (common Filament i18n gap). |
| **P-P1-5** | **English column “الاسم (إنجليزي)”** on Arabic UI | Intentional dual-language model; acceptable if business requires English names in ERP. |

### P2 — Minor / polish

| ID | Issue | Notes |
|----|-------|-------|
| **P-P2-1** | **Locale on in-app links** | Confirm `?lang=ar` persists when clicking **عرض** from lists (same pattern as assets module). |
| **P-P2-2** | **Debug bar visible** | Laravel Debugbar on all pages (dev only). |
| **P-P2-3** | **High query/model count** | ~146 queries / 10k+ models on list load — performance review separately. |

### Not tested (out of scope / no data)

- Payroll batch **generate payslips**, **mark paid**, **WPS export**, **post to accounting** (no batch records).
- Payslip **view**, **recalculate**, **PDF/email** actions.
- Loan **approval workflow**, installments relation manager.
- Employee assignment **create/save** full flow (browser automation limited on Livewire selects).
- **My Payslips** employee page (`MyPayslips.php`) — not in admin top nav.

---

## Settings cluster (إعدادات الرواتب)

| Item | Status |
|------|--------|
| Configuration cluster label | **إعدادات الرواتب** (Arabic) |
| Salary components (CRUD) | List/create/view/edit UI Arabic |
| Accounting fields on component | Section **المحاسبة** present when accounting plugin available |
| Separate accounting settings page | None — accounting is per salary component |

---

## Automated tests

```bash
php artisan test --compact tests/Feature/PayrollArabicLabelsTest.php tests/Feature/Navigation/SalesArabicLabelsTest.php
```

---

## Recommendations

1. **Deploy the P0 fix** before any Arabic payroll UAT (already in working tree).
2. **Use `name_ar` for record titles** in Filament when locale is Arabic.
3. **Manual smoke** after deploy: create one payroll batch → generate payslips → view payslip PDF (Arabic footer/OMR).
4. **Optional:** Add Pest/Livewire tests for batch create + generate (see existing [`tests/Feature/Payroll/PayrollPluginTest.php`](tests/Feature/Payroll/PayrollPluginTest.php)).

---

## Files changed (fix + QA)

- `app/Providers/SalesExtensionsServiceProvider.php` — remove shared `pluralModelLabel` mutation
- `app/Filament/Sales/Pages/ListQuotations.php` — explicit Arabic list title
- `plugins/webkul/payroll/src/Filament/Resources/*Resource.php` — `getPluralModelLabel()`
- `plugins/webkul/payroll/resources/lang/{en,ar}/payroll.php` — `models_plural`
- `plugins/webkul/payroll/src/Filament/Resources/*/Pages/List*.php` — empty state
- `tests/Feature/PayrollArabicLabelsTest.php` (new)
- `tests/Feature/Navigation/SalesArabicLabelsTest.php` (updated)
