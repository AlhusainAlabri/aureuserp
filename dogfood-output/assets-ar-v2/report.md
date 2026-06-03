# Assets Module — Arabic Browser QA Report (Post S2–S7)

**Date:** 2026-05-31  
**Environment:** Local `http://127.0.0.1:8000/admin`  
**Locale:** Arabic (`?lang=ar`)  
**Tester:** Automated browser pass (`agent-browser`)  
**Credentials:** `nodhumtech@gmail.com` / `Oman@999` (`auth.md`)

---

## Executive summary

| Metric | Result |
|--------|--------|
| Checklist | **18 Pass**, **4 Partial**, **2 Fail** |
| Critical blockers | **2** — borrowing view page 500; vehicle fields hidden on create |
| Arabic UI | **Strong** — navigation, hub, tables, modals, badges |
| Screenshots | `dogfood-output/assets-ar-v2/screenshots/` |

Compared to the pre-implementation report (`dogfood-output/assets-ar/report.md`), the hub dashboard, request pages, signatures infrastructure, categories, and audit resource are now present. Two new **P0** regressions were found in browser testing.

---

## Full test checklist

| # | Area | Scenario | Result | Notes |
|---|------|----------|--------|-------|
| 1 | Auth | Login page Arabic labels | **Pass** | البريد الإلكتروني، كلمة المرور، تسجيل الدخول |
| 2 | Auth | Locale after login | **Partial** | Redirect to `/admin` drops `?lang=ar` |
| 3 | Main dashboard | Assets summary widget (AR) | **Pass** | طلبات معلّقة / overdue stats visible |
| 4 | Hub | `/admin/assets/dashboard?lang=ar` loads | **Pass** | Was 404 before; now **لوحة الأصول** |
| 5 | Hub | Stats widgets Arabic | **Pass** | متاح، متأخر، طلبات معلّقة، charts |
| 6 | Hub | Table widgets (due soon, overdue, recent) | **Pass** | Arabic headings and status badges |
| 7 | Navigation | Sidebar group **إدارة الأصول** | **Pass** | 5 items: hub, pending, my requests, my borrowed, assets |
| 8 | List | Asset list + tabs | **Pass** | Arabic columns, tabs, OMR **ر.ع.** |
| 9 | List | View link locale | **Fail** | **عرض** → `/admin/assets/assets/29` (no `lang=ar`) |
| 10 | Create | Form Arabic labels | **Pass** | إنشاء أصل، تفاصيل الأصل |
| 11 | Create | Category enum dropdown | **Pass** | مركبة، أثاث، معدات |
| 12 | Create | Vehicle fields when category = vehicle | **Fail** | **رقم اللوحة** fields not shown after selecting مركبة |
| 13 | View | Available asset + borrow action | **Pass** | **إعارة الأصل**; URL keeps `lang=ar` on direct open |
| 14 | View | Borrowed asset + return action | **Pass** | **إرجاع الأصل**; status **مُعار**; borrower infolist |
| 15 | Borrow | Modal Arabic labels | **Pass** | الموظف، تاريخ الاستحقاق، ملاحظات |
| 16 | Return | Signature modal | **Partial** | Automation could not reliably open modal; signature pad not verified in browser |
| 17 | My requests | Page loads | **Pass** | **طلبات الإعارة** title, table columns Arabic |
| 18 | My requests | **طلب إعارة أصل** header button | **Fail** | Button missing — custom Blade view omits header actions |
| 19 | Pending | Pending requests page | **Pass** | **طلبات الإعارة المعلّقة** loads |
| 20 | My borrowed | My borrowed assets page | **Pass** | **أصولي المُعارة** loads |
| 21 | Borrowing view | `/admin/assets/borrowings/{id}?lang=ar` | **Fail** | **500 LogicException** — no index page on resource |
| 22 | Audit / approval | View borrowing infolist + events | **Blocked** | Blocked by #21 |
| 23 | Data | Demo names in Arabic UI | **Partial** | English factory names (e.g. Demo Toyota Hilux) in tables |
| 24 | Console | JS errors on tested pages | **Pass** | No Livewire/JS errors observed (Debugbar only in dev) |

---

## Findings by priority

### P0 — Blockers

| ID | Issue | Evidence |
|----|-------|----------|
| **B-P0-1** | **View borrowing page crashes (500)** | `LogicException`: `AssetBorrowingResource` has no `[index]` page. URL: `/admin/assets/borrowings/1?lang=ar`. Blocks audit trail, approval actions, approve/reject with signature, PDF export. |
| **B-P0-2** | **Vehicle fields never appear on create/edit** | After selecting **مركبة**, `رقم اللوحة` / registration / mileage fields stay hidden. Likely `Get $get('category') === 'vehicle'` mismatch with enum-backed Select state. |

### P1 — UX / i18n

| ID | Issue | Evidence |
|----|-------|----------|
| **B-P1-1** | **Request asset button missing** on **طلبات الإعارة** | `borrowing-requests.blade.php` only renders `{{ $this->table }}`; `getHeaderActions()` with `RequestAssetAction` never shown. Admin without linked employee also wouldn't see it by design. |
| **B-P1-2** | **Locale drops on list → view** | Table **عرض** link: `/admin/assets/assets/29` without `?lang=ar`. Direct URL with locale works. |
| **B-P1-3** | **Locale drops after login** | Login with `?lang=ar` → redirect `/admin` (LTR until user switches language). |
| **B-P1-4** | **English demo asset names in Arabic UI** | Factory/seed names (`Demo Toyota Hilux`, raw category values in old rows). Run `AssetsDemoSeeder` for Arabic demo data. |

### P2 — Polish / verify manually

| ID | Issue | Notes |
|----|-------|-------|
| **B-P2-1** | Return signature pad | Return modal + `saade/filament-autograph` canvas not confirmed in automation; verify manually after P0 fixes. |
| **B-P2-2** | Employee Select search placeholder | Re-check borrow/request modals for English `"Search"` leak (fixed in plugin lang in code; not re-verified this pass). |
| **B-P2-3** | Approve/reject + approval workflow UI | Blocked until B-P0-1 fixed; configure Approval Flow for `AssetBorrowing` in admin. |

---

## What works well (Arabic)

- **لوحة الأصول** hub with stats, due-soon/overdue/recent tables, category chart
- Full **إدارة الأصول** navigation (5 pages)
- Borrowing request pages with Arabic table headers
- Asset CRUD form mostly Arabic; category enum labels translated
- Borrow/return action labels and borrowed-asset infolist (**مُعار إلى**, **تاريخ الاستحقاق**)
- Main dashboard assets summary widget
- View page keeps locale when opened with `?lang=ar`

---

## Screenshots index

| File | Description |
|------|-------------|
| `01-main-dashboard-ar.png` | Main dashboard |
| `02-assets-hub-ar.png` | Assets hub |
| `03-asset-list-ar.png` | Asset list |
| `04-my-borrowing-requests-ar.png` | My borrowing requests |
| `05-pending-requests-ar.png` | Pending requests |
| `06-my-borrowed-assets-ar.png` | My borrowed assets |
| `07-create-asset-ar.png` | Create form |
| `08-category-dropdown-ar.png` | Category dropdown AR |
| `09-vehicle-fields-ar.png` | After vehicle select (fields missing) |
| `10-view-available-asset-ar.png` | View available asset |
| `11-borrow-modal-ar.png` | Borrow modal |
| `12-view-borrowed-asset-ar.png` | View borrowed asset |
| `13-return-signature-modal-ar.png` | Return flow attempt |
| `15-hub-widgets-ar.png` | Hub widgets detail |
| `16-view-borrowing-ar.png` | **500 error** on borrowing view |
| `17-main-dashboard-assets-widget-ar.png` | Main dashboard widgets |

---

## Recommended fixes (in order)

1. **B-P0-1** — Add `getIndexUrl()` on `AssetBorrowingResource` or `ViewAssetBorrowing` pointing to `PendingBorrowingRequests` or `AssetsDashboard`.
2. **B-P0-2** — Fix vehicle field visibility: compare enum value safely, e.g. `AssetCategory::tryFrom($get('category')) === AssetCategory::Vehicle`.
3. **B-P1-1** — Replace custom Blade with default Filament page layout, or add `<x-filament-panels::header />` / render header actions in `borrowing-requests.blade.php`.
4. **B-P1-2** — Ensure list **عرض** URLs use `FilamentUrl::withLocale()` (verify `ListAssets` override).
5. **B-P1-3** — Preserve `lang` query on post-login redirect.
6. Run `php artisan db:seed --class=AssetsDemoSeeder` for Arabic demo QA data.

---

## Automated tests (reference)

```
php artisan test --compact tests/Feature/Assets/
Tests: 26 passed (72 assertions)
```

Browser-only issues (500, hidden fields, missing header button) are **not** covered by current Pest suite.
