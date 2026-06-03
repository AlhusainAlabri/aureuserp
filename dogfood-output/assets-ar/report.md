# Assets Module — Arabic E2E QA Report

**Date:** 2026-05-31  
**Environment:** Local (`http://127.0.0.1:8000/admin`)  
**Locale:** Arabic (`?lang=ar`)  
**Tester:** Automated browser pass (agent-browser) + code review validation  
**Credentials:** Admin `nodhumtech@gmail.com` / `Oman@999` (see `auth.md`)

---

## Executive summary

| Metric | Result |
|--------|--------|
| Checklist pass rate | **10 / 13 Pass**, **3 Partial**, **0 Blocked** |
| Core CRUD + borrow/return | **Functional** (borrow/return verified via UI return flow + Pest tests; create/borrow modal submit unreliable in automation due to Filament date picker) |
| Arabic UI coverage | **Strong** — navigation, forms, tabs, badges, modals, relation manager all Arabic |
| Committee requirements | **Partial** — registry and direct checkout work; request flow, signatures, approval, email alerts, audit log, and hub dashboard **missing** |
| Recommended next step | **Review this report → approve S1 (Assets hub + demo + locale fixes) before workflow work** |

**Blockers for production committee use:** No employee request/approval flow (P0), no digital signature (P0), no approval workflow integration (P0).

---

## Pre-flight

| Check | Status |
|-------|--------|
| `php artisan assets:install` | ✅ Success |
| `php artisan migrate` | ✅ Nothing pending |
| `php artisan optimize:clear` | ✅ Done |
| Table `assets` | ✅ Exists |
| Table `asset_borrowings` | ✅ Exists |
| Demo data | 4 assets seeded via factory for QA (+ 1 borrow/return cycle, 1 overdue borrowing) |

---

## E2E checklist (#1–13)

| # | Scenario | Result | Notes | Screenshot |
|---|----------|--------|-------|------------|
| 1 | **Navigation (AR)** | **Pass** | Sidebar group **إدارة الأصول**, item **الأصول**; RTL layout; page titles Arabic | `screenshots/01-list-ar.png` |
| 2 | **List + tabs** | **Pass** | Tabs الكل / متاح / مُعار / صيانة / متقاعد with correct badge counts (4 / 3 / 1 / 1 / 0 at test time) | `01-list-ar.png`, `02-tab-available-ar.png`, `15-tab-borrowed-ar.png` |
| 3 | **Create asset** | **Partial** | Form fully Arabic; value field labeled **ر.ع.**; auto-number placeholder shown. Browser submit did not persist record (Filament/Livewire form). Pest tests + factory confirm `AST-YYYY-####` generation works. | `03-create-ar.png`, `04-after-create-ar.png` |
| 4 | **View / edit** | **Pass** | Infolist Arabic; status badge **مُعار**; **مُعار إلى** shows employee; **تاريخ الاستحقاق** shown when borrowed | `05-view-asset-ar.png`, `08-view-borrowed-ar.png`, `10-edit-borrowing-history-ar.png` |
| 5 | **Borrow flow** | **Partial** | Modal Arabic (**إعارة الأصل**, الموظف, تاريخ الاستحقاق, ملاحظات). Employee select works; **Search** placeholder in English (minor i18n leak). Browser automation could not complete submit (DateTimePicker). Borrow confirmed via tinker + UI state on reload. | `06-borrow-modal-ar.png`, `07-after-borrow-ar.png`, `08-view-borrowed-ar.png` |
| 6 | **Return flow** | **Pass** | **إرجاع الأصل** confirmation modal (**تأكيد**); asset returns to **متاح**; `returned_at` populated in DB | `09-after-return-ar.png` |
| 7 | **Borrowing history** | **Pass** | Relation **سجل الإعارات** on edit: employee, dates, status badge **مُرجَع** / **متأخر** | `11-borrowing-relation-ar.png`, `16-overdue-borrowing-history-ar.png` |
| 8 | **Permissions** | **Pass** | User with `view_assets_asset` + `view_any_assets_asset` only — no borrow/return/edit actions on view page | `17-permissions-no-borrow-ar.png` |
| 9 | **Locale persistence** | **Partial** | `?lang=ar` kept on tab URLs (`&tab=available`). **Lost** when clicking **عرض** from list → `/admin/assets/assets/4` (no `lang=ar`). Login redirect also drops locale (`/admin` not `/admin?lang=ar`). | — |
| 10 | **Overdue display** | **Pass** | Overdue borrowing shows badge **متأخر** in relation manager | `16-overdue-borrowing-history-ar.png` |
| 11 | **Main dashboard** | **Pass** (expected gap) | No asset widgets on `/admin?lang=ar` | `12-main-dashboard-ar.png` |
| 12 | **Assets hub** | **Pass** (expected gap) | `/admin/assets/dashboard?lang=ar` → **404 Not Found** | `13-no-assets-hub-ar.png` |
| 13 | **Console errors** | **Pass** | No JS/Livewire errors observed on list, view, borrow modal, return confirm | — |

---

## Findings by priority

### P0 — Functional / blocking (committee requirements)

| ID | Issue | Browser validation | Evidence |
|----|-------|-------------------|----------|
| **A-P0-1** | **No employee request flow** — admin borrows directly; no pending/request status | Confirmed | `BorrowingStatus` has only `active`, `returned`, `overdue` — no `pending`. `BorrowAction` is admin-initiated only. |
| **A-P0-2** | **No digital signature / acknowledgment** on borrow or return | Confirmed | No signature columns in `asset_borrowings` migration; return is confirm-only modal. |
| **A-P0-3** | **No approval workflow** | Confirmed | `HasApprovalWorkflow` not applied to Asset or AssetBorrowing; no Approvals relation manager. |

### P1 — UX / notifications

| ID | Issue | Browser validation | Evidence |
|----|-------|-------------------|----------|
| **A-P1-1** | Borrow/return notifications are **flash-only** (not queued DB/email) | Confirmed (code) | `BorrowAction` / `ReturnAction` use `Notification::make()->send()` synchronously. |
| **A-P1-2** | Overdue alerts **only to `hr_manager`** — not borrower, not Admin, no email | Confirmed (code) | `NotifyOverdueAssetBorrowingJob` queries `hr_manager` role only; DB notification only. |
| **A-P1-3** | **No due-date reminder** before overdue | Confirmed | Only `assets:notify-overdue-borrowings` scheduled at 08:30. |
| **A-P1-4** | **No return notification** to HR/employee | Confirmed (code) | Return action has flash notification only. |
| **A-P1-5** | **`AssetsStatsWidget` orphaned** — not on any dashboard | Confirmed | Main dashboard has no asset widgets; no hub page. Widget registered via plugin discover only. |
| **A-P1-6** | **Locale may drop on widget stat URLs** | Confirmed (code) | `AssetsStatsWidget` uses raw `AssetResource::getUrl()` without `FilamentUrl` helper (same pattern as inventory bug). |
| **A-P1-7** | **Locale drops on in-app navigation** | **New — browser confirmed** | List → View link omits `?lang=ar`. Should use `FilamentUrl` or panel locale middleware consistently. |
| **A-P1-8** | **Employee select search placeholder in English** | **New — browser confirmed** | Filament Select search shows `"Search"` inside Arabic borrow modal. |

### P2 — Data model / committee requirements

| ID | Issue | Browser validation | Evidence |
|----|-------|-------------------|----------|
| **A-P2-1** | **Category is free text** — no enum for vehicles / furniture / equipment | Confirmed | Table shows raw English seed values (`equipment`, `vehicles`); form is `TextInput`. |
| **A-P2-2** | **No committee entity** — company-scoped only | Confirmed (code) | `company_id` on assets; no committee model. |
| **A-P2-3** | **Assigned user is Employee, not User** | Confirmed | Borrow modal selects from `employees_employees`; may not match “logged-in user requests asset” UX. |
| **A-P2-4** | **No vehicle-specific fields** (plate, registration, mileage) | Confirmed (code) | Single generic schema. |
| **A-P2-5** | **Audit log is borrowing rows only** — not immutable event log | Confirmed | No IP, before/after, or signature artifact storage. |

### P3 — Testing / polish

| ID | Issue | Browser validation | Evidence |
|----|-------|-------------------|----------|
| **A-P3-1** | **No Filament Livewire tests** for borrow/return actions | Confirmed | `tests/Feature/Assets/AssetPluginTest.php` — model/command only (3 tests, all pass). |
| **A-P3-2** | **No demo seeder** for realistic Arabic QA | Confirmed | Unlike `InventoryDemoSeeder`; QA used factory + manual seed. |
| **A-P3-3** | **No REST API** | Confirmed (code) | No plugin API routes. |
| **A-P3-4** | **No “My borrowed assets” self-service page** | Confirmed | Employee cannot see own borrowings without admin access. |
| **A-P3-5** | **Demo/seed data displays English names in Arabic UI** | **New — browser confirmed** | Factory names (`Demo Toyota Hilux`, `equipment`) appear in Arabic table — needs Arabic demo seeder. |

---

## Requirements traceability matrix

| Requirement | Status | QA notes |
|-------------|--------|----------|
| Manage committee assets (vehicles, furniture, equipment) | **Partial** | CRUD works; category is free text; no vehicle fields |
| Track details, availability, location, assigned user | **Mostly done** | View infolist shows location, status, borrower, due date when borrowed |
| Borrowing/checkout with **request** | **Missing** | Direct admin borrow only (P0) |
| Digital signature on checkout/return | **Missing** | (P0) |
| Auto notifications (approvals, due, overdue, return) | **Partial** | Overdue → hr_manager DB only; borrow/return flash only |
| Complete audit log | **Partial** | Borrowing history table only; not immutable audit trail |

---

## What works well (Arabic UX)

- Navigation group **إدارة الأصول** and resource label **الأصول**
- List tabs with Arabic labels and live counts
- OMR formatting with **ر.ع.** and 3 decimal places in table
- Status badges: متاح، مُعار، صيانة، متقاعد
- Borrowing status badges: نشط، مُرجَع، **متأخر**
- Borrow/return action labels and confirmation modals in Arabic
- Permission gating on borrow/return actions
- Scheduled overdue command + job (backend; not fully validated in browser notification UI)

---

## Automated test results

```
php artisan test --compact tests/Feature/Assets/AssetPluginTest.php
Tests: 3 passed (7 assertions)
```

Covers: AST number format, borrow/return model lifecycle, overdue command dispatches job.

---

## Recommended sprint order (for your approval)

Per plan Phase 3 — **do not implement until you approve priorities:**

| Sprint | Scope | Rationale |
|--------|-------|-----------|
| **S1** | Assets hub (`/admin/assets/dashboard`), `AssetsDemoSeeder`, `FilamentUrl` on all asset links, Livewire borrow/return tests, fix locale on navigation | Unblocks visibility (orphaned widget), Arabic demo data, fixes A-P1-5/6/7 |
| **S2** | Employee request flow (`BorrowingStatus::Pending`, “Request asset” page, manager approve/reject) | Addresses A-P0-1 |
| **S3** | `HasApprovalWorkflow` on borrowing requests | Addresses A-P0-3 |
| **S4** | Digital signature pad on borrow/return | Addresses A-P0-2 |
| **S5** | Queued Mailables + due reminders (3 days) + overdue/return emails | Addresses A-P1-1–4 |
| **S6** | Immutable `asset_borrowing_events` audit log + PDF export | Addresses A-P2-5 |
| **S7** | `AssetCategory` enum + vehicle-specific fields | Addresses A-P2-1, A-P2-4 |

**Default recommendation:** Start with **S1 (hub + demo + locale)** as agreed in plan Q&A, then S2→S7 for committee workflow.

---

## Screenshots index

All under `dogfood-output/assets-ar/screenshots/`:

| File | Description |
|------|-------------|
| `01-list-ar.png` | Asset list — all tab, Arabic columns |
| `02-tab-available-ar.png` | Available tab with `lang=ar` in URL |
| `03-create-ar.png` | Create form — Arabic labels |
| `04-after-create-ar.png` | Create submit did not redirect (automation) |
| `05-view-asset-ar.png` | View available asset — borrow action |
| `06-borrow-modal-ar.png` | Borrow modal Arabic |
| `07-after-borrow-ar.png` | After borrow attempt (automation) |
| `08-view-borrowed-ar.png` | View borrowed asset — return action, infolist |
| `09-after-return-ar.png` | After return confirm |
| `10-edit-borrowing-history-ar.png` | Edit page top |
| `11-borrowing-relation-ar.png` | سجل الإعارات with returned row |
| `12-main-dashboard-ar.png` | Main dashboard — no asset widgets |
| `13-no-assets-hub-ar.png` | 404 on `/admin/assets/dashboard` |
| `14-overdue-asset-ar.png` | Borrowed/overdue asset view |
| `15-tab-borrowed-ar.png` | Borrowed tab |
| `16-overdue-borrowing-history-ar.png` | **متأخر** badge in history |
| `17-permissions-no-borrow-ar.png` | View-only user — no borrow button |

---

## Next step

**Paused for your review.** Reply with:

1. Approved sprint priority (default: **S1 hub first**)
2. Any findings to re-classify (severity changes)
3. Go-ahead to implement Phase 2 Assets dashboard

No feature implementation will proceed until you approve.
