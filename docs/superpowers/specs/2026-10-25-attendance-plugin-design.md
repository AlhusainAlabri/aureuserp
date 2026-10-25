# Attendance plugin and payroll integration — design

Date: 2026-10-25 · Sub-project 3 of 3 · New plugin `plugins/webkul/attendances`; payroll changes in `plugins/webkul/payroll`

## Goal
Record who actually worked, from three input paths (self check-in, device import, HR manual entry), and let payroll use it for absence, lateness and approved overtime — each switchable independently.

## Today
- `CalendarAttendance` (`calendar_attendances`) defines weekly hours per `Calendar`; `Employee.calendar_id` links an employee to one. `CalendarLeave` holds global holidays. Employee also has `attendance_manager_id`, `barcode`, `pin`, `is_flexible`, `is_fully_flexible`, `time_zone`.
- Time Off (`Leave`, states `validate_one`/`validate_two`) marks approved leave.
- `PayrollCalculator` uses a constant 22 working days; only unpaid leave reduces pay.
- `maatwebsite/excel` is installed, so the import accepts CSV and XLSX without new dependencies.

## Decisions
- Separate plugin, installable/optional like Time Off; follows `aureuserp-plugin-builder` conventions (service provider, `AttendancesPlugin`, policies, Shield permissions, translations `ar`/`en`).
- Payroll uses the attendance summary **only when the plugin is installed**; otherwise payroll keeps the legacy 22-day month, so existing payslips do not change.
- Overtime is detected automatically but only approved overtime reaches payroll.
- Lateness is a per-minute deduction only in v1 ("N late arrivals = half a day" is a follow-up).
- Legal and policy numbers (grace minutes, overtime multipliers) are settings, not constants.

## Data
`attendance_punches` — raw log: `employee_id`, `punched_at` (UTC), `source` (`self`/`device`/`manual`), `direction` (`in`/`out`/null for unknown), `device_ref`, `import_batch_id` null, `ip`, `latitude`, `longitude`, `creator_id`. Unique key `(employee_id, punched_at, source)` for de-duplication.

`attendance_days` — one row per employee per local date: `first_in`, `last_out`, `worked_minutes`, `expected_minutes`, `late_minutes`, `early_leave_minutes`, `overtime_minutes`, `status` (`present`/`absent`/`leave`/`holiday`/`rest`), `is_manual_override`, `override_note`, `edited_by`, `leave_id` null. Unique `(employee_id, date)`.

`attendance_overtime_requests` — `employee_id`, `date`, `minutes`, `day_type` (`normal`/`rest`/`holiday`), `multiplier`, `status` (`pending`/`approved`/`rejected`), `decided_by`, `decided_at`, `note`. Generated from day computation; unique `(employee_id, date)`.

`attendance_import_batches` — file name, row counts, unmatched device IDs (json), `created_by`.

`employees_employees`: use the existing `barcode` as the device user ID (labelled "Attendance device ID" in the form); no new column.

Settings (`AttendanceSettings`): `grace_minutes` (default 10), `overtime_multiplier_normal` 1.25, `overtime_multiplier_rest` 1.5, `overtime_multiplier_holiday` 1.5, `minimum_overtime_minutes` (default 30), `allow_self_checkin` true, `allowed_ip_ranges` (list), `geofence` (lat, lng, radius metres; optional), `workday_close_time`.
Payroll settings (`PayrollAttendanceSettings`): `deduct_absence`, `deduct_lateness`, `pay_overtime` (all default false).

## Components
- `PunchRecorder::record(...)` — single entry point used by all three paths; validates, inserts, then dispatches `RecalculateAttendanceDay`.
- `AttendanceDayCalculator` — for an employee and date: expected hours from the employee's calendar (`CalendarAttendance` rows for that weekday/week type; flexible employees: expected minutes only, no lateness), holiday via `CalendarLeave`, leave via approved Time Off, worked time from first/last punch, lateness beyond shift start + grace, early leave, overtime beyond shift end (or all worked time on rest/holiday) at or above `minimum_overtime_minutes`. Manual override rows are never recomputed.
- Nightly command `attendance:close-day` marks working days without punches/leave/holiday as `absent` and creates pending overtime requests.
- `SelfCheckIn` Filament page "My attendance": Check in / Check out, today's punches, month list; enforces `allow_self_checkin`, IP ranges, geofence.
- Device import page: upload CSV/XLSX → map columns (device user ID, timestamp, optional direction) → preview (matched, unmatched IDs, duplicates) → confirm; creates an import batch and punches.
- HR monthly sheet: grid per employee/month to create or fix days (sets `is_manual_override`, keeps punches, records `edited_by`/note).
- Overtime requests resource: HR/manager (`attendance_manager_id`) approves/rejects, bulk approve.
- Reports: daily attendance, monthly summary, late and absent lists; CSV export.
- `AttendanceSummary::forMonth(Employee, year, month)`: `working_days` (from calendar minus holidays), `absent_days`, `late_minutes`, `overtime_hours` grouped by multiplier (approved only).

## Payroll integration
- `PayrollCalculator` asks `AttendanceSummary` when the plugin is installed: `working_days` replaces the constant 22; when `deduct_absence`, `absent_days` are added to unpaid days (basic pro-rated as today); when `deduct_lateness`, a deduction line `LATE` = late minutes × hourly rate (hourly = basic ÷ working days ÷ shift hours); when `pay_overtime`, earning lines `OVERTIME` = hours × hourly × multiplier.
- `payslips` gain `absent_days`, `late_minutes`, `overtime_hours`; payslip view and PDF show them.
- If the plugin is absent, behaviour is unchanged (22 days, no new lines).

## Out of scope
Biometric device live API/SDK, shift rosters/rotations, "N lates = half day" rule, mobile app, attendance-based leave accrual.

## Testing (Pest, feature)
- Day calculator matrix: normal day, late within/over grace, early leave, overtime below/above minimum, rest day, holiday, approved leave, flexible employee, manual override preserved.
- `PunchRecorder`: duplicate punches, IP/geofence rejection, self check-in disabled.
- Import: CSV and XLSX, unmatched IDs, duplicates, re-import idempotence.
- Nightly close: absent vs leave vs holiday; overtime request creation.
- Summary: real working days; only approved overtime counted.
- Payroll: each toggle on/off; legacy 22-day behaviour when the plugin is not installed; payslip PDF fields.
- Permissions: employee sees own data, manager their team, HR everything.
