# Attendance Plugin Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Record who actually worked (self check-in, device import, HR manual entry) in a new optional `attendances` plugin and let payroll use it for absence, lateness and approved overtime, each switchable.

**Architecture:** One pipeline: every source writes `attendance_punches` through `PunchRecorder`, which recalculates the day (`AttendanceDayCalculator` → `attendance_days`, plus pending overtime requests). A nightly `attendance:close-day` marks absences. `AttendanceSummary::forMonth` feeds payroll through a small `AttendanceBridge` in the payroll plugin that is inert when the plugin is not installed.

**Tech Stack:** PHP 8.4, Laravel 13, Filament 5, Pest 4, MySQL (`nodhum_erp_test`, shared and transactional), spatie/laravel-settings, `maatwebsite/excel` (already installed), Symfony `IpUtils`.

**Spec:** `docs/superpowers/specs/2026-10-25-attendance-plugin-design.md`

## Global Constraints
- Plugin: folder `plugins/webkul/attendances`, namespace `Webkul\Attendance`, plugin/package name `attendances`, install command `attendances:install`, translation namespace `attendances`, depends on `employees`. Follow the `aureuserp-plugin-builder` skill and `payroll` as the model (`PayrollServiceProvider`, `PayrollPlugin`, `composer.json` with `extra.laravel.providers`, `config/filament-shield.php`). The plugin is picked up by `composer dump-autoload` (root `composer.json` merges `plugins/*/*/composer.json`); no dependency changes.
- Tables exactly as the spec's Data section: `attendance_punches`, `attendance_days`, `attendance_overtime_requests`, `attendance_import_batches`. Settings: `AttendanceSettings` (group `attendance`) with the spec's defaults — `grace_minutes` 10, `overtime_multiplier_normal` 1.25, `overtime_multiplier_rest` 1.5, `overtime_multiplier_holiday` 1.5, `minimum_overtime_minutes` 30, `allow_self_checkin` true, `allowed_ip_ranges` [], `geofence` null (`{latitude, longitude, radius_metres}`), `workday_close_time` `23:30`. Payroll: `PayrollAttendanceSettings` (group `payroll_attendance`): `deduct_absence`, `deduct_lateness`, `pay_overtime`, all false. Register both classes in `config/settings.php`.
- Punches are stored in UTC. The local date of a punch is computed in `$employee->time_zone ?: config('app.timezone')` (`Asia/Muscat`); `attendance_days.date` is that local date.
- Schedule = the employee's calendar rows in `calendar_attendances` for the weekday (`day_of_week` is `monday`…`sunday`; `hour_from`/`hour_to` are string decimal hours like `"8"`, `"13.5"`). Rows with `day_period = 'lunch'` are breaks and are not counted; shift start = earliest `hour_from`, shift end = latest `hour_to` of counted rows, expected minutes = sum of counted lengths. For two-week calendars (`calendars.two_weeks_calendar`) keep rows whose `week_type` is null or equals `(string) CalendarAttendance::getWeekType($date)`. A weekday with no counted rows is a weekly rest day.
- Calendar = `employees_employees.calendar_id`, else the lowest-id active calendar of the employee's company, else the lowest-id active calendar without a company; none = **no schedule**.
- Holiday = `calendar_leaves` row with `time_type = 'leave'`, `resource_id` null, covering the date, whose `calendar_id` is null or the employee's calendar and `company_id` null or the employee's company. Leave = `time_off_leaves` row of the employee in state `validate_one|validate_two` overlapping the date (only checked when the table exists).
- Money/time: minutes are integers; hours in the summary and payroll are rounded to 2 decimals; payroll money rounds to 3 decimals.
- `employees_employees.attendance_manager_id` is a **user id**; the device user id is the employee's existing `barcode` column (no new column).
- Strings in `plugins/webkul/attendances/resources/lang/{en,ar}` (nested keys, as the skill describes); `TranslationKeysTest` must stay green. Permission keys are not guessed: read them with `FilamentShield::getResourcePermissions()` / `getPages()` and pin them with a test, then add them to `ExtensionPermissionsSync`.
- After adding migrations run `php artisan migrate --env=testing` once before tests; the plugin tables are created in tests by `FeatureTestBootstrap::bootstrap(['attendances' => 'attendance_punches'])`. Unit tests are not transactional: Feature tests only.
- The working tree has unrelated uncommitted files: `git add` explicit paths only; `vendor/bin/pint --dirty --format agent` before each commit; no new dependencies.
- Branch `feature/attendance` (created from `master`, this plan committed on it).

## Decisions beyond the spec (rulings made while planning)
1. Punch → day recalculation runs synchronously inside `PunchRecorder` (no queued job).
2. The "HR monthly sheet" is a normal list resource with create/edit (manual override) filtered by month and employee, not a grid.
3. "Flexible" means the **employee's** `is_flexible` or `is_fully_flexible`; the calendar's `flexible_hours` is ignored (the default calendar ships with it on).
4. Worked time subtracts the overlap with the calendar's lunch rows. With no schedule, attendance is informational: no absence, no lateness, no overtime request.
5. A day with a single punch has `first_in` and no `last_out`: present, worked 0, lateness computed, no overtime.
6. A decided (approved/rejected) overtime request is never changed by recalculation; a pending one follows the recalculated minutes and is deleted when overtime disappears.
7. Late/absence rules are not applied to employees whose `departure_date` is before the date or who are inactive.

## Review Focus
1. A punch at 23:30 Asia/Muscat (19:30 UTC) lands on the right local day, and 00:10 local (20:10 UTC the day before) on the next (Task 2).
2. Single punch, duplicate/same-second punches and unsorted device rows give the right first-in/last-out and never double count (Tasks 2, 4, 5).
3. An employee without a calendar: no absence, no overtime requests flood, payroll keeps the legacy 22 days (Tasks 2, 3, 8).
4. Running `attendance:close-day` or importing the same file twice is idempotent; manual overrides are never recomputed; decided overtime is untouched (Tasks 3, 5, 6).
5. Payroll toggles are independent, only **approved** overtime is paid, the plugin absent means legacy numbers, and recalculating a payslip twice never duplicates `LATE`/`OVERTIME` lines (Task 8).

## File Structure
- Create `plugins/webkul/attendances/` — `composer.json`, `config/filament-shield.php`, `database/migrations/*` (4 tables), `database/settings/*`, `database/factories/*`, `src/AttendanceServiceProvider.php`, `src/AttendancePlugin.php`, `src/Enums/{PunchSource,PunchDirection,DayStatus,OvertimeDayType,OvertimeStatus}.php`, `src/Models/{AttendancePunch,AttendanceDay,OvertimeRequest,ImportBatch}.php`, `src/Settings/AttendanceSettings.php`, `src/Services/{WorkScheduleResolver,WorkDay,AttendanceDayCalculator,PunchRecorder,SelfCheckInService,OvertimeRequestSync,AttendanceSummary,AttendanceMonthSummary,AttendanceImporter,ImportPreview,AttendanceScope}.php`, `src/Exceptions/AttendanceException.php`, `src/Console/CloseDayCommand.php`, `src/Policies/*`, `src/Filament/{Pages,Resources,Clusters}/*`, `resources/{lang,views}/*`.
- Create (payroll): `database/migrations/2026_10_27_100000_add_attendance_columns_to_payroll_payslips_table.php`, `database/settings/2026_10_27_100001_create_payroll_attendance_settings.php`, `src/Settings/PayrollAttendanceSettings.php`, `src/Services/AttendanceBridge.php`, `src/Filament/Pages/ManagePayrollAttendanceSettings.php`; modify `PayrollCalculator`, `Payslip`, `PayrollServiceProvider`, `PayslipResource` infolist, the payslip PDF view, lang.
- Modify (app): `config/settings.php`, `app/Providers/Filament/AdminPanelProvider.php` (navigation group `icon-attendance`), `app/Support/ModuleLauncher/ModuleLauncherItems.php`, `app/Support/AdminNavigationMenu.php`, `app/Support/ExtensionPermissionsSync.php`, `routes/console.php`; create `resources/svg/attendance.svg`.
- Tests: `tests/Helpers/AttendanceFixtures.php`, `tests/Feature/Attendance/{Schema,Schedule,DayCalculation,CloseDay,SelfCheckIn,Import,Resources,Summary}Test.php`, `tests/Feature/Payroll/AttendancePayrollTest.php`; docs `docs/qa/12-attendance.md`.

---

### Task 1: Plugin skeleton, schema, settings, navigation

**Files:** everything under "Create `plugins/webkul/attendances/`" that is structural (composer.json, provider, plugin, migrations, enums, models, settings, factories, base lang), `AttendanceFixtures`, `AttendanceSchemaTest`; modify `config/settings.php`, `AdminPanelProvider`, `ModuleLauncherItems` (`ICON_COLORS`, `moduleIconOptions`), `AdminNavigationMenu::PRIMARY_URL_PATTERNS`; create `resources/svg/attendance.svg` (style of `resources/svg/payroll.svg`, a clock on a card).

**Interfaces:**
- Produces: enums `PunchSource` (`self`,`device`,`manual`), `PunchDirection` (`in`,`out`), `DayStatus` (`present`,`absent`,`leave`,`holiday`,`rest`), `OvertimeDayType` (`normal`,`rest`,`holiday`), `OvertimeStatus` (`pending`,`approved`,`rejected`); models `AttendancePunch` (casts: `punched_at` datetime, `source`, `direction`), `AttendanceDay` (casts: `date`, `first_in`, `last_out` datetime, `status`, `is_manual_override` bool), `OvertimeRequest` (casts `date`, `day_type`, `status`, `multiplier` decimal:2), `ImportBatch`; relations to `Employee`; `AttendanceSettings` (property types as in Global Constraints); `Tests\Helpers\AttendanceFixtures` with `company(): Company`, `calendar(?Company $c = null, array $weekdays = [...mon–fri 08:00–17:00 with lunch 12–13 and morning/afternoon rows...], bool $twoWeeks = false): Calendar`, `employee(Company $c, ?Calendar $cal, array $attrs = []): Employee` (sets `user_id`, `time_zone` null, optional `barcode`), `holiday(string $from, string $to, ?int $calendarId = null): CalendarLeave`, `punch(Employee $e, string $localDateTime, PunchSource $s = PunchSource::Manual): AttendancePunch` (converts the local time to UTC and inserts directly).

- [ ] **Step 1: Write `AttendanceSchemaTest.php`** (`FeatureTestBootstrap::bootstrap(['attendances' => 'attendance_punches'])`): the four tables have every spec column and the unique keys `attendance_punches (employee_id, punched_at, source)` and `attendance_days (employee_id, date)` and `attendance_overtime_requests (employee_id, date)` (assert duplicate inserts throw); `AttendanceSettings` and `PayrollAttendanceSettings` defaults resolve; enum labels exist in both locales; the plugin id is installed (`Package::isPluginInstalled('attendances')`); `ModuleLauncherItems::colorForIcon('icon-attendance')` is not the `gray` fallback.
- [ ] **Step 2: Run** `php artisan test --compact --filter=AttendanceSchemaTest` — expect FAIL.
- [ ] **Step 3: Implement** the skeleton per the `aureuserp-plugin-builder` checklist and the payroll provider (`name('attendances')`, `hasViews()`, `hasTranslations()`, `hasMigrations`, `runsMigrations`, `hasSettings`, `runsSettings`, `hasDependencies(['employees'])`, install command with `shield:generate` for its resources in `endWith`, `icon('attendance')`), the plugin class with `Package::isPluginInstalled` guard discovering `src/Filament/{Resources,Pages,Clusters}`, the migrations and models (columns, indexes, foreign keys to `employees_employees` cascade-on-delete, `creator_id` nullable to `users`), the settings migration, register `AttendanceSettings` in `config/settings.php`, add the navigation group (label key `attendances::attendances.navigation.group`, icon `icon-attendance`) right after payroll, the launcher/menu entries (landing pattern `/admin/attendances/my-attendance` and `/admin/attendances/days`), the svg, and the base lang files. Run `composer dump-autoload`.
- [ ] **Step 4:** `php artisan attendances:install --no-interaction --env=testing` then `php artisan migrate --env=testing`, then run `php artisan test --compact --filter="AttendanceSchemaTest|TranslationKeysTest|ModuleLauncher"` — expect PASS.
- [ ] **Step 5: Commit** `git add plugins/webkul/attendances config/settings.php app/Providers/Filament/AdminPanelProvider.php app/Support/ModuleLauncher/ModuleLauncherItems.php app/Support/AdminNavigationMenu.php resources/svg/attendance.svg tests/Helpers/AttendanceFixtures.php tests/Feature/Attendance` — "Add the attendances plugin skeleton, schema and settings."

---

### Task 2: Work schedule and day calculation

**Files:** `WorkScheduleResolver`, `WorkDay`, `AttendanceDayCalculator`, `OvertimeRequestSync`, `PunchRecorder`, `AttendanceScheduleTest`, `AttendanceDayCalculationTest`.

**Interfaces:**
- Consumes: Task 1 models/settings/fixtures.
- Produces: `WorkDay` (readonly: `bool $isWorkingDay`, `bool $hasSchedule`, `?int $startMinute` and `?int $endMinute` minutes from local midnight, `int $expectedMinutes`, `list<array{from:int,to:int}> $lunch`, `?string $holidayName`, `bool $isHoliday`, `bool $isRestDay`); `WorkScheduleResolver::forEmployee(Employee, CarbonInterface $date): WorkDay` and `::calendarFor(Employee): ?Calendar`; `AttendanceDayCalculator::calculate(Employee, CarbonInterface $date): AttendanceDay` (computes from that local day's punches, persists with `updateOrCreate` on `(employee_id, date)`, returns without touching a row whose `is_manual_override` is true, then calls `OvertimeRequestSync`); `OvertimeRequestSync::sync(AttendanceDay): void`; `PunchRecorder::record(Employee, CarbonInterface $at, PunchSource $source, ?PunchDirection $direction = null, array $meta = []): AttendancePunch` (inserts or returns the existing identical `(employee, at, source)` punch, then recalculates the punch's local day).

- [ ] **Step 1: Write the two test files:** schedule — weekday rows exclude lunch, decimal hours (`"8.5"`), a rest day (Friday without rows), the two-week filter, calendar fallback order (employee → company → company-less → none), holidays by calendar/company scope and date range, no calendar = `hasSchedule false`; day calculation (grace 10, minimum overtime 30, shift 08:00–17:00 with lunch 12–13) — on time 08:00–17:00 = present, worked 480; arrives 08:25 → late 15; leaves 16:30 → early 30; leaves 18:00 → overtime 60 (pending request, multiplier 1.25, `day_type normal`); 17:20 → no overtime; worked minutes subtract lunch when the employee punched across it; rest day punches 09:00–13:00 → overtime 240 `rest` 1.5; holiday punches → `holiday` 1.5; holiday/rest without punches create no request; approved leave without punches → `leave`; single punch → present, worked 0, lateness set, no overtime; flexible employee → no lateness/early leave, overtime only beyond expected minutes on working days; no schedule → present on punches, no late/early/overtime/request; duplicate record call returns the same punch id; Review Focus 1 (23:30 and 00:10 local punches, 19:30/20:10 UTC) and 2 (unsorted punches, same-minute duplicates); manual override row is not recomputed by a later punch; a decided request is untouched while a pending one follows the minutes and is deleted when they drop below the minimum; `departure_date` before the date or inactive employee → nothing created.
- [ ] **Step 2: Run** `php artisan test --compact --filter="AttendanceScheduleTest|AttendanceDayCalculationTest"` — expect FAIL.
- [ ] **Step 3: Implement** per the signatures and Global Constraints (convert punches to the employee's local zone to pick the day; first punch = in, last = out regardless of the stored `direction`; lateness = max(0, first_in − (shift start + grace)); early leave = max(0, shift end − last_out) when a distinct last_out exists; overtime = max(0, last_out − shift end) on working days or all worked minutes on rest/holiday, only when ≥ `minimum_overtime_minutes`; request multiplier snapshot from settings by day type).
- [ ] **Step 4: Run** the same filter — expect PASS.
- [ ] **Step 5: Commit** `git add plugins/webkul/attendances tests/Feature/Attendance` — "Calculate attendance days from punches and the work calendar."

---

### Task 3: Nightly close, absences and the monthly summary

**Files:** `CloseDayCommand`, scheduler entry, `AttendanceSummary`, `AttendanceMonthSummary`, `AttendanceCloseDayTest`, `AttendanceSummaryTest`; modify `routes/console.php`, provider (`->hasCommands([...])`, as `PluginManagerServiceProvider` does).

**Interfaces:**
- Consumes: Task 2.
- Produces: `php artisan attendance:close-day {--date=}` (default: yesterday in the app timezone; for every active employee not departed: ensure the day is calculated; a working day with no punches, no leave and no holiday becomes `absent`; idempotent) scheduled daily at `AttendanceSettings->workday_close_time`; `AttendanceMonthSummary` (readonly: `bool $hasSchedule`, `int $workingDays`, `int $absentDays`, `int $lateMinutes`, `float $dailyHours`, `array<string,float> $overtimeHours` keyed by multiplier string e.g. `'1.25'`); `AttendanceSummary::forMonth(Employee, int $year, int $month): AttendanceMonthSummary` (working days = days in the month that are working days of the schedule and not holidays; absent/late from closed `attendance_days` rows; overtime = approved requests only, grouped by their stored multiplier; `dailyHours` = expected minutes over working days ÷ 60 ÷ working days).

- [ ] **Step 1: Write the tests:** the command marks absent for a working day without punches, not for holidays, rest days, approved leave days, employees without schedule (Review Focus 3), departed/inactive employees; running it twice and for a past date leaves one row per employee/day; a day that has punches keeps its status; the scheduler contains the command; summary: working days for a month with a holiday, absent/late sums, overtime grouped by multiplier counting only `approved` requests (pending and rejected excluded), `hasSchedule false` for an employee without calendar, `dailyHours` 8.0 for the fixture calendar, other employees' rows ignored.
- [ ] **Step 2: Run** `php artisan test --compact --filter="AttendanceCloseDayTest|AttendanceSummaryTest"` — expect FAIL.
- [ ] **Step 3: Implement** per the interfaces; the command lives in `src/Console` and is registered through the provider; schedule it in `routes/console.php` like the existing entries (`Schedule::command('attendance:close-day')->dailyAt(...)` reading the setting guarded by `Schema::hasTable`).
- [ ] **Step 4: Run** the same filter — expect PASS.
- [ ] **Step 5: Commit** `git add plugins/webkul/attendances routes/console.php tests/Feature/Attendance` — "Close attendance days nightly and summarise months."

---

### Task 4: Self check-in

**Files:** `SelfCheckInService`, `AttendanceException`, `Filament/Pages/MyAttendance.php` + view, `AttendanceSelfCheckInTest`.

**Interfaces:**
- Consumes: `PunchRecorder`, `AttendanceSettings`.
- Produces: `SelfCheckInService::punch(Employee, ?string $ip, ?float $latitude, ?float $longitude): AttendancePunch` (direction alternates: no punch today or last is `out` → `in`, else `out`; throws `AttendanceException` with a translated message when `allow_self_checkin` is off, when `allowed_ip_ranges` is non-empty and `Symfony\Component\HttpFoundation\IpUtils::checkIp($ip, $ranges)` fails, when a geofence is set and coordinates are missing or farther than `radius_metres` by the haversine distance, or when the previous punch is under 60 seconds old); `MyAttendance` page (slug `attendances/my-attendance`, no permission, shown to any authenticated user with an `Employee` whose `user_id` is the current user): today's punches, a Check in / Check out button (Alpine `navigator.geolocation` fills latitude/longitude when a geofence exists), and the month's own days.

- [ ] **Step 1: Write the tests:** alternating in/out; each refusal with its message and no punch row; CIDR and exact IPs; geofence inside/outside/missing coordinates; the 60-second guard; page renders for an employee, shows a notification on refusal, is hidden/forbidden for a user without an employee; a self punch recalculates the day (late minutes appear).
- [ ] **Step 2: Run** `php artisan test --compact --filter=AttendanceSelfCheckInTest` — expect FAIL.
- [ ] **Step 3: Implement** the service, exception, page and view (model the page on `MyPayslips`); pass `request()->ip()` from the page.
- [ ] **Step 4: Run** the same filter — expect PASS.
- [ ] **Step 5: Commit** `git add plugins/webkul/attendances tests/Feature/Attendance` — "Add self check-in and the My attendance page."

---

### Task 5: Device import

**Files:** `AttendanceImporter`, `ImportPreview`, `Filament/Pages/ImportAttendance.php` + view, `AttendanceImportTest`.

**Interfaces:**
- Consumes: `PunchRecorder`, `ImportBatch`.
- Produces: `AttendanceImporter::read(string $path, string $extension): array{headers: list<string>, rows: list<array<int,mixed>>}` (CSV and XLSX through `Maatwebsite\Excel\Facades\Excel::toArray`; if the facade rejects a plain object importable, use `toCollection` — decide in the first test); `AttendanceImporter::preview(array $rows, array $mapping, bool $hasHeader): ImportPreview` where `$mapping = ['device_user_id' => int, 'timestamp' => int, 'direction' => ?int]`; `ImportPreview` (readonly: `list<array{employee_id:int,at:CarbonImmutable,direction:?PunchDirection}> $matched`, `list<string> $unmatchedDeviceIds`, `int $duplicates`, `int $invalidRows`); `AttendanceImporter::commit(ImportPreview, User $by, string $fileName): ImportBatch` (records the batch counts and unmatched ids, creates punches with source `device` through `PunchRecorder`, skips ones that already exist). `ImportAttendance` page (`attendances/import`, permission page): upload CSV/XLSX → choose columns → preview table (matched, unmatched ids, duplicates) → confirm.

- [ ] **Step 1: Write the tests:** CSV and XLSX (generate with `Excel::raw`/a temp file) read the same rows; mapping with and without header; timestamps parsed in the app timezone and stored UTC; direction column optional (`in`/`out`/`1`/`0`); device ids matched on `barcode` after trimming; unmatched ids listed once; blank/invalid timestamps counted as invalid; unsorted rows give the right first/last (Review Focus 2); importing the same file twice creates no new punches and the second batch reports all as duplicates (Review Focus 4); affected days are recalculated; the page flow through `Livewire::test` (upload, map, preview, confirm) creates the batch; forbidden without the page permission.
- [ ] **Step 2: Run** `php artisan test --compact --filter=AttendanceImportTest` — expect FAIL.
- [ ] **Step 3: Implement** the importer and the page, and add the helper text "Attendance device user ID" (en/ar) to the employee form's `barcode` field in `plugins/webkul/employees/src/Filament/Resources/EmployeeResource.php` (around line 838; text only, no behaviour change). The page is a three-step Filament page using `FileUpload` with a temporary private disk; model the step handling on a simple `Page` with public state, not a package wizard).
- [ ] **Step 4: Run** the same filter — expect PASS.
- [ ] **Step 5: Commit** `git add plugins/webkul/attendances tests/Feature/Attendance` — "Add the device attendance import."

---

### Task 6: Day and overtime screens, scoping, permissions

**Files:** `AttendanceDayResource` (+ List/Create/Edit pages), `OvertimeRequestResource` (+ List page), `AttendanceScope`, policies, `AttendanceResourcesTest`; modify `ExtensionPermissionsSync`, `config/filament-shield.php` of the plugin.

**Interfaces:**
- Consumes: Tasks 2–3.
- Produces: `AttendanceScope::employeeIdsFor(User): ?Collection` (null = all employees when the user holds the day resource's `update_*` permission; otherwise the user's own employee id plus employees whose `attendance_manager_id` is the user); `AttendanceDayResource` (list columns employee, date, first in/out, worked, late, early, overtime, status; tabs All / Late / Absent / Overtime; filters month and employee; CSV export header action via `App\Support\Exports\Utf8Csv`; create/edit as manual entry: employee, date, first in, last out, status, required note → sets `is_manual_override`, `edited_by`, keeps punches, recalculates nothing); `OvertimeRequestResource` (list with status tabs; row actions Approve/Reject with an optional note; bulk Approve; decided rows are read-only); policies mirroring `PasiRulePolicy` (no delete) with `approve`/`reject` abilities requiring the update permission and a `pending` status; `ExtensionPermissionsSync::syncAttendance()` called from `syncAll()`: read/team permissions to `manager`, `department_manager`, `hr_manager`, `Admin`, `super_admin`; update permissions to `hr_manager`, `Admin`, `super_admin`; the My attendance page and import page keys per the page rule.

- [ ] **Step 1: Write the tests:** list scoping (HR sees all; a manager sees team + self; a plain permitted viewer sees self only; an unrelated user is forbidden); tabs and filters; manual create/edit sets override, requires the note, and a later punch does not recompute the row (Review Focus 4); CSV content (BOM, header, rows); approve/reject/bulk approve change status, store `decided_by`/`decided_at`, make the request count in `AttendanceSummary`, and cannot be repeated; policy keys equal Shield's; `syncAttendance` grants as listed, twice, without widening.
- [ ] **Step 2: Run** `php artisan test --compact --filter=AttendanceResourcesTest` — expect FAIL.
- [ ] **Step 3: Implement** the resources, pages, scope and policies (patterns: `PasiRuleResource`, `GratuityTierResource`, `EndOfServiceSettlementResource`; pages remap `ValidationException` keys to `data.*`).
- [ ] **Step 4: Run** `php artisan test --compact --filter="AttendanceResourcesTest|ExtensionPermissionsSyncTest|PasiPermissionsSyncTest|TranslationKeysTest"` — expect PASS.
- [ ] **Step 5: Commit** `git add plugins/webkul/attendances app/Support/ExtensionPermissionsSync.php tests/Feature/Attendance` — "Add attendance day and overtime screens with team scoping."

---

### Task 7: Monthly summary report

**Files:** `Filament/Pages/MonthlyAttendanceSummary.php` + view, `AttendanceMonthlyReportTest`.

**Interfaces:** consumes `AttendanceSummary`, `AttendanceScope`; produces a page (slug `attendances/monthly-summary`, `HasPageShield`) with year/month selectors, one row per visible employee (name, working days, absent days, late minutes, approved overtime hours) and a CSV export action (`attendance-summary-YYYY-MM.csv`).

- [ ] **Step 1: Write the tests:** rows equal `AttendanceSummary` for each scoped employee; team scoping; employees without a schedule show dashes; CSV content and filename; permission key pinned to Shield.
- [ ] **Step 2: Run** `php artisan test --compact --filter=AttendanceMonthlyReportTest` — expect FAIL.
- [ ] **Step 3: Implement** the page like `PasiContributionSchedule`.
- [ ] **Step 4: Run** the same filter — expect PASS.
- [ ] **Step 5: Commit** `git add plugins/webkul/attendances tests/Feature/Attendance` — "Add the monthly attendance summary report."

---

### Task 8: Payroll integration

**Files:** payroll — `AttendanceBridge`, `PayrollAttendanceSettings`, `ManagePayrollAttendanceSettings`, settings and payslip-columns migrations, `Payslip` fillable/casts (`absent_days` decimal:1, `late_minutes` int, `overtime_hours` decimal:2), `PayrollCalculator`, payslip infolist and PDF view, lang, provider; `AttendancePayrollTest`.

**Interfaces:**
- Consumes: `AttendanceSummary::forMonth` (through the bridge only; payroll never imports attendance classes outside the bridge).
- Produces: `AttendanceBridge::available(): bool` (`Schema::hasTable('attendance_days')` and the summary class exists) and `AttendanceBridge::summary(Employee, int $year, int $month): ?array{has_schedule: bool, working_days: int, absent_days: int, late_minutes: int, daily_hours: float, overtime_hours: array<string,float>}` (null when unavailable or `has_schedule` is false); in `PayrollCalculator::buildPayslip`: `$workingDays` = summary working days when the summary exists, else the legacy `22.0` (remove the class constant use from `prorateBasic`, pass the value); with `deduct_absence`, `$workedDays = max($workingDays − unpaidLeaveDays − absentDays, 0)` and `payslip.absent_days` stored; with `deduct_lateness`, a deduction line `{component_id: null, code: 'LATE', type: deduction, amount: round(lateMinutes ÷ 60 × hourly, 3)}` where `hourly = contractualBasic ÷ workingDays ÷ dailyHours`; with `pay_overtime`, one earning line `{component_id: null, code: 'OVERTIME', amount: round(Σ hours × hourly × multiplier, 3)}`; `late_minutes`/`overtime_hours` stored on the payslip whenever a summary exists (informational even when the toggle is off); lines are rebuilt on recalculation (the existing line deletion keeps this idempotent); the payslip view and PDF show working days, absent days, late minutes and overtime hours when a summary exists.

- [ ] **Step 1: Write `AttendancePayrollTest.php`** (uses `AttendanceFixtures` and the payroll fixtures; replace the bridge with a stub subclass bound in the container to simulate "plugin absent"): legacy numbers (22 days, no new lines, columns null) when the bridge is unavailable and when the employee has no schedule (Review Focus 3); with the plugin and all toggles off, `working_days` follows the calendar (e.g. 21 for a month with 21 working days and one holiday) and nothing is deducted; `deduct_absence` alone reduces basic by `absent_days / working_days`; `deduct_lateness` alone adds a `LATE` line of `late ÷ 60 × hourly` (e.g. basic 840, 21 days, 8 h, 90 late minutes = `840/21/8 × 1.5 = 7.500`); `pay_overtime` alone adds `OVERTIME` from **approved** requests only, each at its own multiplier, pending/rejected ignored (Review Focus 5); toggles do not affect each other; recalculating twice leaves one `LATE` and one `OVERTIME` line; net = earnings − deductions; PASI base is unaffected by the new lines; payslip infolist and PDF contain the four attendance figures; the settings page saves the toggles for a permitted user.
- [ ] **Step 2: Run** `php artisan migrate --env=testing`, then `php artisan test --compact --filter=AttendancePayrollTest` — expect FAIL.
- [ ] **Step 3: Implement** per the interface block; register the payslip migration in `hasMigrations`, the settings migration in `hasSettings`, `PayrollAttendanceSettings` in `config/settings.php`, the settings page in the payroll `Configuration` cluster (pattern `ManageGratuitySettings`, page key pinned to Shield and added to `syncPayroll`).
- [ ] **Step 4: Run** `php artisan test --compact --filter="AttendancePayrollTest|Payroll|Pasi|Gratuity|TranslationKeysTest"` — expect PASS (the legacy payroll suites must be unchanged).
- [ ] **Step 5: Commit** `git add plugins/webkul/payroll config/settings.php app/Support/ExtensionPermissionsSync.php tests/Feature/Payroll/AttendancePayrollTest.php` — "Let payroll use attendance for absence, lateness and overtime."

---

### Task 9: Verification and rollout notes

**Files:** `docs/qa/12-attendance.md` (new; no other QA file is edited).

- [ ] **Step 1:** `vendor/bin/pint --dirty --format agent`, then `php artisan test tests --compact` in the background (10–15 minutes; run nothing else on the test database meanwhile) — expect 0 failures.
- [ ] **Step 2:** On the local QA database run `php artisan attendances:install --no-interaction`, `php artisan migrate`, `php artisan permissions:sync-extensions`; in the browser (Arabic) give QA employee 11 a calendar, check in and out on "My attendance", import a small CSV (device id = the employee's barcode), edit a day manually, approve an overtime request, open the monthly summary, and generate a payslip with the toggles on; render the payslip PDF to PNG (`pymupdf`) to check the new figures; remove the test data afterwards. Report what was seen.
- [ ] **Step 3:** Write the QA note: rollout commands (install, not just migrate), the cron/queue reminder (the nightly close needs `schedule:run`), the decisions beyond the spec (above), that the device user id is the employee barcode, that employees need a calendar, that flexible = employee flag, that payroll uses real working days once the plugin is installed, and the out-of-scope list (device API, rosters, "N lates = half day", mobile app, leave accrual from attendance).
- [ ] **Step 4: Commit** `git add docs/qa/12-attendance.md` — "Document the attendance rollout."
