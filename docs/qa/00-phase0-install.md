# QA Ledger — Phase 0: clean install & environment

Date: 2026-10-24 · Env: local, MariaDB 12.2, PHP 8.4, locale `ar` · DB wiped and reinstalled (`migrate:fresh` → `erp:install` → `plugins:install --all --include-core`).
Severity: **B** blocker · **F** functional · **U** UI · **G** gap · **P** polish

| ID | Sev | Finding | Status |
|----|-----|---------|--------|
| P0-01 | B | `erp:install` on a fresh server is blocked by `DestructiveCommandGuard` (it calls `db:seed` internally). A customer install needs `ALLOW_DESTRUCTIVE_ARTISAN_COMMANDS=true`. | **fixed** — guard allows `db:seed` only while a `*:install` command runs (tests added) |
| P0-02 | B | App-level migrations in `database/migrations` that extend plugin tables run before the plugins are installed, no-op on `hasTable()` and are recorded as ran. Missing after a clean install: `purchases_orders.request_type/urgency` (500 on Purchase internal/my requests), purchase payments table, `accounts_account_moves.project_id`, inventory replenishment/consumption tables, `projects_tasks` operations columns. | **fixed** — `erp:ensure-extension-schema` re-runs a listed set of idempotent migrations after every plugin install and on deploy; static test keeps the list complete |
| P0-03 | B | `Webkul\TimeOff\Models\LeaveAccrualLevel` imports `Auth` twice → PHP fatal on any page touching it. | **fixed** |
| P0-04 | B | Default `queue:work` memory limit (128 MB) is lower than app boot memory (~125 MB), so the worker exits with code 12 on its first loop. `scripts/deploy-prod.sh` documents the supervisor command without `--memory`. | **fixed** — script now documents `--memory=512 --max-time=3600` |
| P0-05 | U/G | Fresh install shows another organisation's logo (Zakat Committee, `public/images/logo_2.png` fallback) on login and launcher. Seed company is "DummyCorp LLC" and shows in every page title. Installer never asks for company name. (Branding.php has uncommitted user changes.) | open |
| P0-06 | B | Dashboards 500: `canAccess()` missing on `Projects\TopAssigneesWidget` (/admin/project), `Recruitment\JobPositionStatsWidget` (/admin/recruitment), `TimeOff\LeaveTypeReportWidget` (/admin/time-off/reporting/.../by-type). → module phases | open |
| P0-07 | F | `/admin/settings/dashboard-shortcuts/create` 500: `DashboardShortcutResource.php:133` Svg object cast to string. → Phase 1 | open |
| P0-08 | F | `/admin/account-tags` 500. → Accounting phase | open |
| P0-09 | P | `/admin/email-verification/prompt` 500 (User does not implement `MustVerifyEmail`); direct-URL only. | open |
| P0-10 | P | `Object of class Livewire Redirector could not be converted to int` logged for every cluster-index redirect (PHP 8.4 + Livewire `abort(Redirector)`); responses are still correct 302s, but log noise. | open |
| P0-11 | P | `migrate:fresh` on a DB that already has plugins installed fails (FK errno 150 on `employees_employees`) because plugin migrations register in the wrong order. Works on an empty plugins table. Dev-only. | open |
| P0-12 | G | Scheduler: `inspire` hourly (leftover); `system:backup-run --automatic` every 5 min; `github:check-updates` calls out from the customer server. Review for production. | open |
| P0-13 | G | Env hardening for production: `APP_TIMEZONE=UTC` (Oman = Asia/Muscat), `APP_DEBUG`, debugbar, cache driver `file`, `MAIL_MAILER=log`. → final phase | open |
| P0-14 | P | Launcher dashboard: 155 queries / 3906 models hydrated per load. → performance pass | open |
| P0-15 | G | `plugins:install --all` also installs Website and Blogs plugins (not wanted for a typical ERP customer). Decide default set. | open |
| P0-16 | B | With `APP_ENV=production`, `erp:install` and plugin installs call `migrate` / `db:seed` without `--force`, so Laravel cancels them ("APPLICATION IN PRODUCTION"), the installer still prints success, then crashes creating the admin user. | **fixed** — `--force` added to all nested calls |
| P0-17 | B | `HrExtensionSchemaService` (and `ExtensionSchema::ensureAttachmentTables`) create tables only when `! isProduction()`. In production `employee_contracts`, `employee_self_assessments`, salary-raise FK, warning/anonymous/document-type columns were never created. | **fixed** via the same command (forced HR ensure + second deferred pass); two migrations made FK-idempotent |
| P0-18 | F | 6 tests fail on a clean tree (pre-existing, not from Phase 0): `AdminNavigationGroupsTest` (icons), `EmployeeRecordNavigationTabsTest` (persists…), `HrGapImplementationTest` ×2 (Oman org structure seed, QueryException), `EmployeeRelatedRecordsTest` ×2 (create…, resend warning mail). | open → Employees phase |

Verified: full clean install in **production mode with no override flag** on a scratch DB (`migrate` → `erp:install` → `plugins:install --all --include-core`) succeeds, and its schema (3624 columns, FKs, indexes) is identical to the QA DB. Command is idempotent (run twice).

## Sweep results (499 parameter-free admin GET routes, as admin)
200: 393 · 302: 36 · 403: 62 (59 token-only `/admin/api/v1/*`, 3 `my-*` pages needing an employee profile) · 500: 8 (see P0-06..09).
