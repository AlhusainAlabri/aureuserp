# QA Ledger — Phase 3: employees & HR

Env: QA DB, locale `ar`. UI flows run: employee create (real form), employee overview, contracts tab (create), HR tab availability (13 tabs all load), warnings/documents covered by existing tests.

| ID | Sev | Finding | Status |
|----|-----|---------|--------|
| P3-01 | B | Every user save mirrored all user attributes (incl. `is_active`, `password`) into `partners_partners`, but those columns existed only if the **Website** plugin was installed: creating any user failed on an install without it. Under `db:seed` (unguarded models) other user-only attributes such as `language` also leaked into the INSERT. | **fixed** — core migration `…_add_account_columns_to_partners_partners_table` (Website migration made idempotent); user→partner mirror lists its attributes explicitly; tests |
| P3-02 | F | `projects/pdf/performance-report` and `dashboard/pdf/dashboard-report` used an inline `@php(...)` next to an `@php … @endphp` block, so Blade paired them and swallowed the page: project performance PDF failed. | **fixed** |
| P3-03 | F | Purchase-order API `confirm` returned 500 when the order's approval flow was not finished; `toggle-lock` on an already confirmed order re-triggered the approval guard (500 for orders confirmed before a flow existed). | **fixed** — 422 with a clear message; guard only applies when *entering* a confirmed state; tests |
| P3-04 | U | Employee form: the departments multi-select was labelled "جميع الأقسام" (All departments). | **fixed** — "أقسام الموظف" |
| P3-05 | U | Employee overview and other custom Blade views printed phone numbers with the `+` on the wrong side in RTL (`96824112233+`). Formatter only covered Filament columns/entries. | **fixed** — global script isolates phone/email/URL/code-only elements in RTL pages (test) |
| P3-06 | U | Dates displayed US-style in Arabic ("ديسمبر 31, 2027"). | **fixed** — global `d M Y` / `d M Y h:i A` for tables and infolists |
| P3-07 | U | 19 clusters returned their class name as breadcrumb ("Configurations", "Configuration", "Operations"…), ~56 pages. | **fixed** |
| P3-08 | F | Stale tests encoded old behavior: locale persistence (`?lang=` is per-request, the switcher route persists), mail asserted as sent instead of queued, Oman org seeder names, navigation-group icons, dashboard route, plugin locale middleware. 6 HR tests + 5 others were red. | **fixed** — tests updated to the current design (behavior verified in the browser) |
| P3-09 | G | Upstream demo organisation still seeded: departments "Long Term Projects", "R&D USA"…; job positions are generic English tech roles (now Arabic names). | open → decide per customer (OmanOrgStructureSeeder exists for the Zakat structure) |
| P3-10 | P | `PartnerTest` API list test fails on the shared test DB (22k leftover partner rows push new records off page 1). | open (test-data hygiene) |
| P3-11 | T | Test infrastructure: a DDL test inside the transactional Feature suite polluted later tests. | **fixed** — moved to Unit |

Verified in the browser: employee create → overview (departments, job, civil ID with expiry, phone), contracts tab create (type, dates, wage `850.500 ر.ع.`), 13 HR tabs load, Arabic labels and Omani hints (+968, Asia/Muscat default time zone).
