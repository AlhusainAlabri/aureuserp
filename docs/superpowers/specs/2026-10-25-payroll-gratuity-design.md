# Payroll: end-of-service gratuity — design

Date: 2026-10-25 · Sub-project 2 of 3 · Plugin: `plugins/webkul/payroll` · Depends on: nothing from sub-project 1 except the shared nationality rule

## Goal
When an expat employee leaves, HR gets a correct end-of-service settlement (gratuity, unused-leave payout, loan offset, final salary) with an Arabic statement, approved through the existing approval engine.

## Today
- `EmployeeContract` (`app/Models/Hr`) has `first_joining_date`, `start_date`, `end_date`, `wage`.
- `Employee` has `departure_date`, `departure_reason_id`; `DepartureReason` has only `name`/`reason_code`.
- Salary used by payroll is the `BASIC` `EmployeeComponent`, separate from the contract wage.
- `Loan` / `LoanInstallment` exist. Time Off balances exist in `time_off_leave_allocations` / `time_off_leaves`.

## Assumptions (verify before go-live)
- Default tiers: 15 days of basic per year for the first 3 years, 30 days per year after, pro-rated by days for partial years. This is the author's reading of Omani labour law and is **not verified**; tiers are editable.
- Eligible: expat employees only (Omani staff are covered by PASI), with at least 1 year of service (setting).
- Leave payout = unused annual-leave balance × basic ÷ 30, editable by HR on the settlement.

## Data
`payroll_gratuity_tiers`: `from_year` (decimal), `to_year` (decimal, nullable = open ended), `days_per_year` (decimal(5,2)), `effective_from`, `effective_to`, `creator_id`.

`employees_departure_reasons`: add `pays_gratuity` boolean default true (migration in the payroll plugin; column guarded with `Schema::hasColumn`).

`payroll_settings` (spatie settings, new `GratuitySettings`): `minimum_service_years` (default 1), `eligible_category` (default `expat`), `leave_payout_divisor` (default 30).

`payroll_end_of_service_settlements`:
`id`, `employee_id`, `company_id`, `join_date`, `departure_date`, `service_days`, `last_basic` (decimal 12,3), `gratuity_breakdown` (json: per-tier years, days, amount), `gratuity_amount`, `unused_leave_days`, `leave_payout_amount`, `loan_offset_amount`, `final_salary_amount`, `other_adjustment` (+/- with `adjustment_note`), `net_amount`, `status` (`draft`/`approved`/`paid`), `approved_by`, `approved_at`, `paid_at`, `creator_id`, timestamps, soft deletes. One non-cancelled settlement per employee.

## Components
- `ServicePeriodResolver` — start = earliest `first_joining_date` over the employee's contracts, falling back to the earliest contract `start_date`; end = `departure_date`. Returns null when either is missing (action is disabled with a reason).
- `GratuityCalculator` — `calculate(Employee, Carbon $start, Carbon $end): GratuityResult` with total and per-tier breakdown. Returns zero with a reason when ineligible (Omani; departure reason `pays_gratuity = false`; service below minimum).
- `FinalSettlementBuilder` — assembles the settlement: gratuity, leave payout (only when Time Off is installed), outstanding active-loan balance as an offset, pro-rated salary for the final partial month (reuses `PayrollCalculator`), net.
- Filament: "End-of-service settlement" action on the employee view/departure; `EndOfServiceSettlementResource` (list/view/edit-while-draft) in the payroll navigation; settings page for tiers; approval via the app's approval engine (flow seeded inactive, same pattern as `LoanApprovalFlowSeeder`).
- PDF: Arabic settlement statement through `App\Support\Pdf\Documents\*` and `resources/views/pdf/trade-document.blade.php` layout conventions.
- Permissions follow the payroll policies (`SalaryComponentPolicy` pattern) and Shield sync.

## Existing sites
Migration adds tables/columns, seeds the default tiers and settings. No payslip or employee data changes.

## Out of scope
Notice-period pay, unpaid-leave exclusion from service, EOSB for Omani staff, posting the settlement to accounting (follow-up via `PayrollAccountingService`).

## Testing (Pest, feature)
- Tier boundaries (exactly 3 years, just over), partial years, multi-contract start date, missing dates.
- Eligibility: Omani, expat, `pays_gratuity = false`, below minimum service.
- Builder: loan offset, leave payout on/off with Time Off absent, net total, one settlement per employee.
- Approval gate: cannot mark paid before approval when the flow is active.
- PDF renders (shared Arabic layout) and shows the breakdown.
- Migration/seeder idempotence.
