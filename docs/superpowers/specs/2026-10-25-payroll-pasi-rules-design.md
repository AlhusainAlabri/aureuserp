# Payroll: PASI rules — design

Date: 2026-10-25 · Sub-project 1 of 3 (PASI → gratuity → attendance) · Plugin: `plugins/webkul/payroll`

## Goal
Payroll deducts and accrues social insurance (PASI) the way Omani HR expects: by employee nationality, with rates and a salary cap that HR can edit, without rewriting history when a rate changes.

## Today
- `DefaultSalaryComponentsSeeder` seeds `PASI_EMP` (deduction, 7% of basic) and `PASI_EMR` (employer cost, 11.5% of basic) as `percent_of_basic`.
- `PayrollCalculator::resolveComponentAmount` has no nationality, cap, or date-awareness. Every employee pays the same flat rate on basic.
- `Employee.country_id` is the nationality. The company's country is the Omani reference (to be confirmed against the `Company` model's country column when the implementation plan is written; if absent, a `home_country_id` setting is used instead).

## Assumptions (to be verified by the customer's accountant before go-live)
- Default rates are today's seeded values: Omani 7% employee / 11.5% employer, no cap. Expat 0% / 0%.
- Insurable base = BASIC only (current behaviour). Other earnings can be flagged insurable later.
- The official PASI portal file format is out of scope until a sample file is provided.

## Data
`payroll_pasi_rules`
| column | notes |
|---|---|
| id | |
| category | enum `omani` / `expat` |
| employee_rate | decimal(5,2) percent |
| employer_rate | decimal(5,2) percent |
| salary_cap | decimal(12,3) nullable — maximum insurable salary per month |
| effective_from | date, required |
| effective_to | date nullable (null = current) |
| creator_id, timestamps | |

Rules for one category must not overlap (validated on save). Changing a rate = closing the current row (`effective_to` = day before) and adding a new row; the settings UI does this in one action.

`payroll_salary_components`: add `is_pasi_insurable` boolean, default false; the migration sets it true for code `BASIC`.

`CalculationType`: add `pasi_employee` and `pasi_employer`.

## Components
- `PasiRuleResolver` — `forEmployee(Employee, Carbon $periodEnd): ?PasiRule`. Category = `omani` when `employee.country_id === employee.company.country_id`, else `expat`; returns the rule in force on `$periodEnd`.
- `PasiCalculator` — `base(Payslip): float` = sum of lines whose component `is_pasi_insurable`, capped at `salary_cap`; `employeeShare` / `employerShare` = base × rate.
- `PayrollCalculator` — for components of type `pasi_employee` / `pasi_employer`, delegates to `PasiCalculator`. These components run in the second pass (after earnings exist), like `percent_of_gross` today.
- Missing nationality (`country_id` null) or no rule found: no PASI line, plus a warning line on the payslip (`code = PASI_WARN`, amount 0) so it is visible in the payslip and PDF.
- Settings page "PASI rules" under the payroll configuration cluster (list + add-new-rate action).
- Report `PASI contribution schedule` (page under `PayrollReports` or its own page): month/year filter; columns employee, civil ID, category, insurable salary, employee share, employer share; totals; CSV export via `App\Support\Exports\Utf8Csv`.

## Existing sites
Migration: create table; insert one `omani` row (7.00 / 11.50, no cap, `effective_from` = 2000-01-01) and one `expat` row (0 / 0); set `is_pasi_insurable` on BASIC; switch components `PASI_EMP` and `PASI_EMR` to the new calculation types. Draft payslips recalculate to identical numbers for Omani staff; expat staff stop paying PASI (their rate is 0) — noted in the release notes.

## Out of scope
PASI portal file, work-injury or other insurance branches, per-employee PASI exemption.

## Testing (Pest, feature)
- Resolver: rule by date; boundary on `effective_from`/`effective_to`; Omani vs expat; no rule.
- Calculator: cap applied above the limit; base counts only insurable lines; rounding to 3 decimals.
- Rule overlap validation.
- Payroll integration: Omani payslip lines match the old numbers after migration; mid-year rate change leaves earlier payslips untouched and applies to later ones; missing nationality gives the warning line and no PASI.
- Report totals equal the sum of payslip lines.
- Migration test on a database with the old seeded components.
