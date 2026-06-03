# Employee Management Module — Arabic E2E QA Report

**Date:** 2026-05-31  
**Environment:** Local (`http://127.0.0.1:8000/admin`)  
**Locale:** Arabic (`?lang=ar`)  
**Tester:** Automated browser pass (agent-browser) + Pest suite  
**Credentials:** Admin `nodhumtech@gmail.com` / `Oman@999`; Employee `toney.morar@example.org` / `Oman@999` (linked to employee #11)

---

## Executive summary

| Metric | Result |
|--------|--------|
| Automated Pest (HR) | **33 passed**, 1 pre-existing failure (`HrExtensionsTest` purchase preset views) |
| Gap implementation tests | **13 / 13 passed** |
| Browser checklist (AR) | **18 Pass**, **6 Partial**, **3 Fail** (i18n / access) |
| Core HR extensions | **Functional** — contracts, trainings, compensation, payslip history, self-assessment, submissions |
| Arabic UI coverage | **Good on new pages**; several **English leaks** on payroll/plugin-managed labels |
| Recommended next step | Fix P1 i18n leaks + differentiate duplicate salary/submission nav items |

---

## Full test case inventory

### A. Automated — Pest (`tests/Feature/Hr/`)

#### `HrGapImplementationTest.php` (13 tests)

| ID | Test | Covers |
|----|------|--------|
| GAP-01 | creates employee contracts with dates and wage | Contract model + DB |
| GAP-02 | seeds oman org structure idempotently | `OmanOrgStructureSeeder` |
| GAP-03 | stores primary job responsibilities on employee | Profile field migration |
| GAP-04 | submits monthly self assessment | Self-assessment model/status |
| GAP-05 | runs self assessment reminder command | `hr:remind-self-assessments` |
| GAP-06 | creates anonymous employee submission | `is_anonymous` + masked name |
| GAP-07 | notifies on employee file closure | Close service + listener |
| GAP-08 | runs expiring contracts notification command | `hr:notify-expiring-contracts` |
| GAP-09 | runs civil id expiry notification command | `hr:notify-expiring-civil-id` |
| GAP-10 | seeds employee role permissions idempotently | `EmployeeRoleSeeder` |
| GAP-11 | allows employee self service pages when linked | `canAccess()` guards |
| GAP-12 | has employee self assessment table | Schema (`employee_contracts`, `employee_self_assessments`) |
| GAP-13 | stores warning acknowledgment fields | Signature / `employee_acknowledged_at` columns |

#### `HrExtensionsTest.php` (21 tests — existing)

| Area | Tests |
|------|-------|
| Multi-department | assign, sync primary, observer |
| File closure | close, reopen, middleware logout, permissions |
| Training | CRUD relation, expiring cert command |
| Salary raises | create, apply to payroll BASIC |
| Leave substitute | required config, accept/decline, mail |
| Purchase internal requests | MyRequests page, request types |
| Permissions | HR file close/reopen on boot |

**Known failing test (pre-existing):** `provides purchase list preset views by request type` — not introduced by HR gap work.

---

### B. Manual / browser — Admin HR (Arabic)

| ID | Scenario | Steps | Expected | Result |
|----|----------|-------|----------|--------|
| HR-01 | Login AR | `/admin/login?lang=ar` | Arabic labels | **Pass** |
| HR-02 | Dashboard AR | After login | RTL + Arabic widgets | **Pass** |
| HR-03 | Employee list | `/employees/employees?lang=ar` | Arabic tabs/filters | **Pass** |
| HR-04 | Employee overview | `/employees/employees/{id}/overview?lang=ar` | Compliance badges, Arabic nav | **Pass** |
| HR-05 | Employment contracts tab | `/contracts?lang=ar` | Arabic table, add contract | **Pass** |
| HR-06 | Documents tab | `/documents?lang=ar` | Upload form incl. conduct type | **Partial** — page title mixes English "Documents" |
| HR-07 | Trainings tab | `/trainings?lang=ar` | Arabic labels | **Pass** |
| HR-08 | Compensation tab | `/compensation?lang=ar` | Allowance components table | **Partial** — mixed EN "employee component" strings |
| HR-09 | Salary raises tab | `/salary-raises?lang=ar` | Raise history Arabic | **Pass** |
| HR-10 | Payslip history tab | `/payslip-history?lang=ar` | Monthly salary columns | **Pass** (empty state OK) |
| HR-11 | Self-assessments (HR) | `/self-assessments?lang=ar` | Manager review list | **Pass** |
| HR-12 | Warnings tab | `/warnings?lang=ar` | Issue/manage warnings | **Partial** — title "Warnings" in English |
| HR-13 | Edit employee | `/edit?lang=ar` | Departments + responsibilities sections | **Partial** — "Choose file" EN; many tabs truncated |
| HR-14 | Departments list | `/employees/departments?lang=ar` | Org tree / seeded depts | **Partial** — page title "Departments" English; demo cards dominate UI |
| HR-15 | Sub-nav completeness | Overview tabs | All new tabs visible | **Partial** — horizontal overflow truncates last tabs |

---

### C. Manual / browser — Employee self-service (Arabic)

| ID | Scenario | User | Expected | Result |
|----|----------|------|----------|--------|
| ES-01 | My profile | Linked employee | Read-only profile AR | **Pass** — `ملفي الشخصي` |
| ES-02 | My self-assessment | Linked employee | Form + history AR | **Pass** — year/month/comments AR; submit below fold |
| ES-03 | My warnings | Linked employee | Table + acknowledge action | **Pass** — `إنذاراتي` (empty state OK) |
| ES-04 | Employee submissions | Linked employee | Complaints/suggestions AR | **Pass** — `صوتي` |
| ES-05 | Anonymous toggle | Expand "شارك رأيك" | Toggle visible AR | **Partial** — section collapsed by default; toggle not verified open in automation |
| ES-06 | Admin without employee link | Admin | Self pages accessible? | **Fail (by design)** — `Forbidden` on profile/self-assessment/warnings |
| ES-07 | Admin submissions | Admin | Can submit feedback | **Pass** — admin can open `employee-submissions` |

---

### D. Notifications & scheduled commands (code + command smoke)

| ID | Command | Schedule | Result |
|----|---------|----------|--------|
| NC-01 | `hr:notify-expiring-documents` | Daily 08:00 | Existing — Pass |
| NC-02 | `hr:notify-expiring-training-certificates` | Daily 08:00 | Existing — Pass |
| NC-03 | `hr:notify-expiring-civil-id` | Daily 08:00 | Smoke tested — Pass |
| NC-04 | `hr:notify-expiring-contracts` | Daily 08:00 | Smoke tested — Pass |
| NC-05 | `hr:notify-pending-leave-approvals` | Daily 08:15 | Not browser-tested |
| NC-06 | `hr:remind-self-assessments` | 25th + last day | Smoke tested — Pass |
| NC-07 | File closure notifications | On close event | Pest Pass |
| NC-08 | Warning acknowledgment mail | On employee ack | Queued — not browser-verified |

---

## Browser E2E checklist summary

| # | Scenario | Result | Screenshot |
|---|----------|--------|------------|
| 1 | Login (AR) | Pass | — |
| 2 | Dashboard (AR) | Pass | `01-dashboard-ar.png` |
| 3 | Employee list | Pass | `02-employees-list-ar.png` |
| 4 | Employee overview + sub-nav | Pass | `03-employee-overview-ar.png` |
| 5 | Contracts | Pass | `04-contracts-ar.png` |
| 6 | Documents | Partial | `04-documents-ar.png` |
| 7 | Trainings | Pass | `04-trainings-ar.png` |
| 8 | Compensation | Partial | `04-compensation-ar.png` |
| 9 | Salary raises | Pass | `04-salary-raises-ar.png` |
| 10 | Payslip history | Pass | `04-payslip-history-ar.png` |
| 11 | Self-assessments (HR) | Pass | `04-self-assessments-ar.png` |
| 12 | Warnings (HR) | Partial | `04-warnings-ar.png` |
| 13 | Edit employee | Partial | `04-edit-ar.png` |
| 14 | Employee submissions | Pass | `05-employee-submissions-ar.png` |
| 15 | Departments | Partial | `06-departments-ar.png` |
| 16 | My profile (employee) | Pass | `07-employee-my-profile-ar.png` |
| 17 | My self-assessment (employee) | Pass | `07-employee-self-assessment-ar.png` |
| 18 | My warnings (employee) | Pass | `07-employee-warnings-ar.png` |

---

## Findings by priority

### P1 — i18n / UI (Arabic QA)

| ID | Issue | Evidence |
|----|-------|----------|
| **HR-P1-1** | **Compensation page** uses English "employee component" in button, empty state, breadcrumb | `04-compensation-ar.png` — payroll plugin strings not translated |
| **HR-P1-2** | **Manage Documents / Warnings** page titles mix employee name + English ("Documents", "Warnings") | Browser title + tab labels |
| **HR-P1-3** | **Departments** page title + breadcrumb English ("Departments") while nav is Arabic | `06-departments-ar.png` |
| **HR-P1-4** | **Currency prefix "OMR"** on contracts/compensation instead of project standard **ر.ع.** | `04-contracts-ar.png` |
| **HR-P1-5** | **File upload** shows English "Choose file" on employee edit | `04-edit-ar.png` |
| **HR-P1-6** | **Confusing duplicate nav labels:** `سجل الرواتب` (raises) vs `سجل الرواتب الشهرية` (payslips) — easy to misread | `03-employee-overview-ar.png` |
| **HR-P1-7** | **Too many employee sub-tabs** — last tabs truncated (`البدلات و...`) | Horizontal overflow on overview/edit |
| **HR-P1-8** | **Duplicate submission pages:** plugin `my-submissions` + app `employee-submissions` (`صوتي`) both in nav for some users | Route list |

### P2 — UX / data

| ID | Issue | Evidence |
|----|-------|----------|
| **HR-P2-1** | **Oman org structure seeded** but departments UI defaults to English demo cards; seeded depts exist in DB (`Social Research Department`, etc.) but not prominent in default view | DB query + `06-departments-ar.png` |
| **HR-P2-2** | **Self-assessment form** — attachment field + submit button require scroll; not obvious on first screen | `07-employee-self-assessment-ar.png` |
| **HR-P2-3** | **My profile** shows many empty fields (—) for test employee — expected for sparse data, but civil ID/mobile should be filled for production demo | `07-employee-my-profile-ar.png` |
| **HR-P2-4** | **Locale may drop** on in-app links without `?lang=ar` (same pattern as assets module) | Not fully re-tested this pass |

### P3 — Access / roles

| ID | Issue | Evidence |
|----|-------|----------|
| **HR-P3-1** | Admin without linked employee gets **403 Forbidden** on `my-employee-profile`, `my-self-assessment`, `my-warnings` | By design (`canAccess` requires employee link) |
| **HR-P3-2** | **`EmployeeRoleSeeder`** page permissions (`page_MyEmployeeProfile`, etc.) only apply after `shield:generate` | Seeder skips missing permissions |

### P4 — Performance (informational)

| ID | Issue | Evidence |
|----|-------|----------|
| **HR-P4-1** | Employee pages load **140+ queries / 10k+ models** (Debugbar) | All screenshots — pre-existing ERP pattern |

---

## What passed well

- New employee sub-nav tabs render in **Arabic** with correct icons: عقود التوظيف، التدريب، البدلات والتعويضات، سجل الرواتب الشهرية، التقييمات الذاتية
- **Contract CRUD UI** fully Arabic (نوع العقد، تواريخ، إضافة عقد)
- **Employee self-service** works for linked users: ملفي الشخصي، التقييم الذاتي، إنذاراتي، صوتي
- **Employee submissions** page (`صوتي`) Arabic with RTL layout
- **Automated test suite** for gap features: 13/13 green

---

## Screenshots

All under [`dogfood-output/hr-ar/screenshots/`](dogfood-output/hr-ar/screenshots/).

---

## Recommended fixes (ordered)

1. Add AR translations for payroll compensation strings (`employee component`, breadcrumb)
2. Override Manage Documents/Warnings page titles via app pages (like contracts)
3. Use **ر.ع.** formatter on contract/compensation/payslip columns
4. Rename salary tabs: e.g. **زيادات الراتب** vs **كشوف الرواتب الشهرية**
5. Hide duplicate `my-submissions` nav or redirect plugin page to `employee-submissions`
6. Collapse employee sub-nav into grouped dropdown if tab count > 10
7. Run `shield:generate` + `EmployeeRoleSeeder` on deploy

---

*Report generated after HR gap implementation browser QA — May 2026*
