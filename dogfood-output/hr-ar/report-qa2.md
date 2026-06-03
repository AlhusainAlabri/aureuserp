# Employee HR — Arabic QA Re-run (QA2)

**Date:** 2026-05-31  
**Environment:** Local (`http://127.0.0.1:8000/admin`)  
**Locale:** Arabic (`?lang=ar` + session persistence)  
**Screenshots:** [`screenshots/qa2/`](screenshots/qa2/)

---

## Executive summary

| Metric | Result |
|--------|--------|
| Pest HR suite | **38 passed**, 1 skipped (`tests/Feature/Hr/`) |
| Gap + demo tests | **14 / 14 passed** |
| Critical bug found & fixed | App `ManageWarnings` page not registered on `EmployeeResource` (500 on all employee sub-pages) |
| Arabic admin flows | **Pass** after fix |
| Employee self-service | **Pass** (profile, self-assessment, submissions) |
| Locale persistence | **Pass** — Arabic retained navigating without `?lang=ar` |

---

## Fixes applied this pass

1. **`ManageWarnings` registration** — moved `warnings` route into `EmployeeResourceExtensions::extraPages()` so app page class is registered.
2. **Demo data** — `HrDemoEmployeeSeeder` fills Tony Morar (`toney.morar@example.org`) with civil ID, mobile, responsibilities, Oman department link.
3. **Self-assessment UX** — compact 2-column period fields, sticky submit bar, collapsed history section.
4. **Sub-nav overflow** — primary tabs + **المزيد** dropdown; locale appended to tab URLs.
5. **Locale middleware** — `?lang=ar` persisted in session for authenticated users.

---

## Browser checklist (QA2)

| # | Flow | Result | Screenshot |
|---|------|--------|------------|
| 1 | Dashboard (admin, AR) | Pass | `01-dashboard-admin-ar.png` |
| 2 | Employees list | Pass — title **الموظفون** | `02-employees-list-ar.png` |
| 3 | Employee overview + **المزيد** menu | Pass | `03-employee-overview-ar.png` |
| 4 | Compensation tab | Pass — **إضافة مكون**, Arabic empty state | `04-compensation-ar.png` |
| 5 | Contracts | Pass | `05-contracts-ar.png` |
| 6 | Salary raises tab label **زيادات الراتب** | Pass | `06-salary-raises-ar.png` |
| 7 | Warnings tab title **التحذيرات** | Pass | `07-warnings-ar.png` |
| 8 | Payslip history **كشوف الرواتب** | Pass | `07-payslip-history-ar.png` |
| 9 | Departments title **الأقسام** | Pass | `09-departments-ar.png` |
| 10 | Employee edit (departments section) | Pass | `10-edit-ar.png` |
| 11 | Locale persist (no `?lang=` in URL) | Pass — UI stays Arabic | `11-overview-locale-persist.png` |
| 12 | My self-assessment (employee) | Pass — sticky submit, collapsed history | `10-self-assessment-ar.png` |
| 13 | My profile (employee) | Pass — civil ID **12345678**, mobile filled | `11-my-profile-ar.png` |
| 14 | Employee submissions **صوتي** | Pass | `12-submissions-ar.png` |

---

## Remaining (low priority)

| ID | Issue | Notes |
|----|-------|-------|
| HR-P2-1 | Oman departments still English names in cards | Seeded with `company_id`; Arabic names not added |
| HR-P3-2 | `EmployeeRoleSeeder` needs `shield:generate` on deploy | Operational step |
| HR-P4-1 | ~140 queries / 10k models per employee page | Pre-existing ERP pattern |
| HR-P3-1 | Admin without employee link → 403 on self-service pages | By design |

---

## Commands

```bash
php artisan db:seed --class=HrDemoEmployeeSeeder
php artisan test --compact tests/Feature/Hr/
```

---

*QA2 completed after post-fix verification — May 2026*
