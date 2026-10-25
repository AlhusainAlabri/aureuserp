# Payroll PASI Rules Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Payroll deducts and accrues PASI by employee category (Omani/expat) using dated, editable rules with a salary cap, and HR can print a monthly contribution schedule.

**Architecture:** A dated `payroll_pasi_rules` table plus two new calculation types (`pasi_employee`, `pasi_employer`). `PasiRuleResolver` picks the category and rule, `PasiCalculator` computes the capped base and shares, and `PayrollCalculator` runs PASI components in a final pass after all earnings exist. Settings and the schedule report are Filament pages inside the payroll plugin.

**Tech Stack:** PHP 8.4, Laravel 13, Filament 5, Pest 4, MySQL (`nodhum_erp_test`, shared and transactional), Filament Shield, `App\Support\Exports\Utf8Csv`.

**Spec:** `docs/superpowers/specs/2026-10-25-payroll-pasi-rules-design.md`

## Global Constraints
- Default rules: Omani 7.00% employee / 11.50% employer, no cap; expat 0.00% / 0.00%. `effective_from` of the seeded rows is `2000-01-01`.
- Insurable base = lines of components with `is_pasi_insurable = true`; only `BASIC` is flagged by default.
- Money rounds to 3 decimals (`round($x, 3)`); rates are `decimal(5,2)`.
- Rule for a payslip = rule in force on the **last day of the payslip's month** (whole month, even for a mid-month change).
- Missing nationality, missing company country, or no rule found: no PASI line, plus one zero-amount line `PASI_WARN`.
- Rules for one category never overlap; history is kept (no delete action in the UI).
- Plugin code lives in `plugins/webkul/payroll` (namespace `Webkul\Payroll`); tests in `tests/Feature/Payroll/`; strings in both `resources/lang/en` and `resources/lang/ar` (`TranslationKeysTest` must stay green).
- New plugin migrations must be listed in `PayrollServiceProvider::hasMigrations([...])` to run on installed sites.
- The working tree has unrelated uncommitted branding changes: always `git add` explicit paths, never `-A` or `.`.
- Follow `CLAUDE.md`: curly braces always, explicit types, `vendor/bin/pint --dirty --format agent` before each commit, no new dependencies.
- Implement on a new branch `feature/payroll-pasi-rules` created from `docs/oman-payroll-attendance-specs`.

## Review Focus
1. Employee with no nationality, or company with no country, or no rule in force: payslip still generates, one `PASI_WARN` line, no PASI lines (Task 3).
2. Expat with a 0% rule: no PASI line **and no warning** (Task 3).
3. Basic exactly at the cap vs. one baisa above (Task 3).
4. Rate change effective on the 1st vs. mid-month: the payslip for that month uses the rule in force on its last day; earlier months are untouched (Task 2, Task 3).
5. Recalculating a draft payslip twice never duplicates PASI or warning lines (Task 3).

## File Structure
- Create `plugins/webkul/payroll/database/migrations/2026_10_26_100000_create_payroll_pasi_rules_table.php` — table + default rules.
- Create `plugins/webkul/payroll/database/migrations/2026_10_26_100001_add_is_pasi_insurable_to_payroll_salary_components_table.php` — column + convert seeded components.
- Create `src/Enums/PasiCategory.php`, `src/Models/PasiRule.php`, `database/factories/PasiRuleFactory.php`, `src/Policies/PasiRulePolicy.php`.
- Create `src/Services/PasiDefaults.php`, `PasiRuleManager.php`, `PasiRuleResolver.php`, `PasiCalculator.php`, `PasiScheduleBuilder.php`.
- Modify `src/Enums/CalculationType.php`, `src/Models/SalaryComponent.php`, `src/Services/PayrollCalculator.php`, `src/PayrollServiceProvider.php`, `database/seeders/DefaultSalaryComponentsSeeder.php`, `src/Filament/Resources/SalaryComponentResource.php`.
- Create `src/Filament/Resources/PasiRuleResource.php` (+ `Pages/ListPasiRules.php`, `CreatePasiRule.php`, `EditPasiRule.php`), `src/Filament/Pages/PasiContributionSchedule.php`, `resources/views/filament/pages/pasi-contribution-schedule.blade.php`.
- Modify `resources/lang/{en,ar}/{payroll,enums,payslip}.php`.
- Create `tests/Helpers/PasiFixtures.php`, `tests/Feature/Payroll/Pasi{Schema,Rules,Calculation,Resource,Schedule}Test.php`.

---

### Task 1: Schema, enums, model

**Files:**
- Create: the two migrations, `src/Enums/PasiCategory.php`, `src/Models/PasiRule.php`, `database/factories/PasiRuleFactory.php`, `src/Services/PasiDefaults.php`, `tests/Helpers/PasiFixtures.php`, `tests/Feature/Payroll/PasiSchemaTest.php`
- Modify: `src/Enums/CalculationType.php`, `src/Models/SalaryComponent.php`, `src/PayrollServiceProvider.php`, `database/seeders/DefaultSalaryComponentsSeeder.php`, lang `enums.php` (en, ar)

**Interfaces:**
- Produces: `PasiCategory` enum (`Omani = 'omani'`, `Expat = 'expat'`, `HasLabel`); `PasiRule` model (fillable `category, employee_rate, employer_rate, salary_cap, effective_from, effective_to, creator_id`; casts `PasiCategory`, `decimal:2`, `decimal:3`, `date`); scope `PasiRule::scopeInForce(Builder $q, Carbon $date)`; `CalculationType::PasiEmployee = 'pasi_employee'`, `PasiEmployer = 'pasi_employer'`; `SalaryComponent.is_pasi_insurable` (fillable, bool cast); `PasiDefaults::seedRules(): void` and `PasiDefaults::convertSeededComponents(): void` (both idempotent); `Tests\Helpers\PasiFixtures` with `company(string $countryCode = 'OM'): Company`, `employee(Company $company, ?string $nationalityCode, float $basic = 1000): Employee` (creates BASIC assignment), `components(): array{basic: SalaryComponent, employee: SalaryComponent, employer: SalaryComponent}`, `rule(PasiCategory $c, float $emp, float $emr, ?float $cap, string $from, ?string $to = null): PasiRule`.

- [ ] **Step 1: Write `PasiSchemaTest.php`** with tests (using `FeatureTestBootstrap::bootstrap(['payroll' => 'payroll_salary_components'])` in `beforeEach`, as other payroll tests install the plugin when missing):
  - `it('seeds default omani and expat rules')`: after `PasiDefaults::seedRules()` the Omani row is `7.00/11.50/null/2000-01-01`, the expat row `0.00/0.00`; calling twice leaves exactly two rows.
  - `it('converts seeded PASI components')`: given `PASI_EMP` as `percent_of_basic` (7%) and `PASI_EMR` as `percent_of_basic` (11.5%), after `convertSeededComponents()` their `calculation_type` are `pasi_employee`/`pasi_employer` and `BASIC.is_pasi_insurable` is true; other components untouched; idempotent.
  - `it('fresh seeder uses PASI types')`: after `DefaultSalaryComponentsSeeder` on a database without those codes, `PASI_EMP` has type `pasi_employee`.
  - `it('has calculation type labels in both locales')` for the two new cases.

- [ ] **Step 2: Run** `php artisan test --compact --filter=PasiSchemaTest` — expect FAIL (class not found).

- [ ] **Step 3: Implement** migration 1 (`Schema::create('payroll_pasi_rules')`: `id`, `string category`, `decimal employee_rate 5,2`, `decimal employer_rate 5,2`, `decimal salary_cap 12,3 nullable`, `date effective_from`, `date effective_to nullable`, `creator_id` nullable FK to `users` nullOnDelete, timestamps; index `(category, effective_from)`) whose `up()` ends with `PasiDefaults::seedRules()`; migration 2 (`is_pasi_insurable` boolean default false on `payroll_salary_components`, guarded with `Schema::hasColumn`) whose `up()` ends with `PasiDefaults::convertSeededComponents()`. Add the two new `CalculationType` cases and extend its `getColor` (`info`), `getIcon` (`heroicon-o-shield-check`) matches. In `SalaryComponent`: add `is_pasi_insurable` to `$fillable` and `casts`, and add `CalculationType::PasiEmployee, CalculationType::PasiEmployer => 0` arms to `calculateAmount` (the calculator computes them). Register both migrations in `PayrollServiceProvider::hasMigrations`. Update `DefaultSalaryComponentsSeeder`: `PASI_EMP`/`PASI_EMR` use the new types with `default_percent => null`, `BASIC` gets `is_pasi_insurable => true`, and `run()` ends with `PasiDefaults::seedRules()`. Add en/ar labels for the two calculation types and `pasi_category` (Omani/Expat, "عماني"/"وافد").

Also write `tests/Helpers/PasiFixtures.php` (the signatures above) and `PasiRuleFactory`; the fixtures use `Country::query()->firstOrCreate(['code' => $code], ['name' => $code, ...])` and `Employee::query()->create([...'country_id' => …])` as `PayrollPluginTest` does for employees.

- [ ] **Step 4: Run** `php artisan test --compact --filter=PasiSchemaTest` — expect PASS (the test bootstrap runs pending migrations on `nodhum_erp_test`). Also run `php artisan test --compact --filter=PayrollPluginTest` — expect PASS (no regression).

- [ ] **Step 5: Commit**
```bash
git checkout -b feature/payroll-pasi-rules
git add plugins/webkul/payroll tests/Helpers/PasiFixtures.php tests/Feature/Payroll/PasiSchemaTest.php
git commit -m "Add PASI rules table, calculation types and default rules."
```

---

### Task 2: Rule resolution, overlap validation, rule manager

**Files:**
- Create: `src/Services/PasiRuleResolver.php`, `src/Services/PasiRuleManager.php`, `tests/Feature/Payroll/PasiRulesTest.php`
- Modify: `src/Models/PasiRule.php`, lang `payroll.php` (en, ar)

**Interfaces:**
- Consumes: `PasiRule`, `PasiCategory`, `PasiFixtures` (Task 1).
- Produces: `PasiRuleResolver::categoryFor(Employee $employee): ?PasiCategory` (null when `employee.country_id` or `employee.company.country_id` is null); `PasiRuleResolver::ruleFor(Employee $employee, Carbon $date): ?PasiRule`; `PasiRuleManager::addRule(PasiCategory $category, float $employeeRate, float $employerRate, ?float $salaryCap, Carbon $effectiveFrom): PasiRule`; `PasiRule` throws `Illuminate\Validation\ValidationException` (key `effective_from`, message `payroll::payroll.pasi.overlap`) on `saving` when its date range overlaps another row of the same category.

- [ ] **Step 1: Write `PasiRulesTest.php`:**
  - `categoryFor`: same country → `Omani`; different → `Expat`; employee country null → null; company country null → null.
  - `ruleFor`: boundary dates (`effective_from` day itself is in force; `effective_to` day itself is in force; the day after is not); picks the right category; returns null when no rule.
  - `addRule`: with an open-ended rule from 2026-01-01, adding one from 2027-01-01 sets the old `effective_to` to 2026-12-31 and the new `effective_to` null; adding one with `effective_from` ≤ the open rule's `effective_from` throws `ValidationException`.
  - Overlap: creating or editing a rule so its range overlaps another of the same category throws; the same range in the other category is allowed.
  - Review Focus 4: with a rate change effective 2027-03-15, `ruleFor($employee, Carbon::create(2027, 3, 31))` returns the new rule and `Carbon::create(2027, 2, 28)` the old.

- [ ] **Step 2: Run** `php artisan test --compact --filter=PasiRulesTest` — expect FAIL.

- [ ] **Step 3: Implement** the resolver (compare country ids; query `PasiRule::inForce($date)->where('category', …)`), the manager (one DB transaction: lock the open-ended rule of the category, close it at `effective_from - 1 day`, create the new row, `creator_id` = `Auth::id()`), and the overlap check in `PasiRule::booted()` `saving` (ignore its own id; open `effective_to` means no upper bound). Add `pasi.overlap` translations.

- [ ] **Step 4: Run** `php artisan test --compact --filter=PasiRulesTest` — expect PASS.

- [ ] **Step 5: Commit** `git add plugins/webkul/payroll tests/Feature/Payroll/PasiRulesTest.php && git commit -m "Resolve PASI category and dated rules, prevent overlaps."`

---

### Task 3: PASI calculation inside payroll

**Files:**
- Create: `src/Services/PasiCalculator.php`, `tests/Feature/Payroll/PasiCalculationTest.php`
- Modify: `src/Services/PayrollCalculator.php`, lang `payslip.php` (en, ar)

**Interfaces:**
- Consumes: `PasiRuleResolver::ruleFor`, `PasiRule` (Task 2), `SalaryComponent.is_pasi_insurable` (Task 1).
- Produces: `PasiCalculator::base(Payslip $payslip, PasiRule $rule): float` (sum of the payslip's `earning` lines whose component is `is_pasi_insurable`, capped at `salary_cap` when set, rounded to 3); `PasiCalculator::employeeShare(Payslip $payslip, PasiRule $rule): float` and `employerShare(...)` (`round(base × rate / 100, 3)`).

- [ ] **Step 1: Write `PasiCalculationTest.php`** (generate payslips with `PayrollCalculator::calculatePayslip($employee, 2027, 3)`; BASIC 1000 fixed, `PASI_EMP`/`PASI_EMR` from `PasiFixtures::components()`):
  - Omani with default rule: deduction line 70.000, employer-cost line 115.000, net = gross − 70.000.
  - Cap: cap 800 with basic 1000 → employee 56.000 / employer 92.000; basic exactly 800 → 56.000; basic 800.001 → still 56.000 (Review Focus 3).
  - Only insurable lines count: housing (earning, not insurable) does not change the share; flagging it insurable does.
  - Expat with 0% rule → no PASI lines and no `PASI_WARN` (Review Focus 2); expat with a 1% employer-only rule → only the employer line.
  - Missing nationality, missing company country, and no rule in force → no PASI lines, exactly one `PASI_WARN` line with amount 0 (Review Focus 1); payslip net unaffected.
  - Mid-year change (Review Focus 4): payslips for 2027-02 and 2027-03 with a rule change effective 2027-03-15 use old and new rates; regenerating February afterwards still uses the old rate.
  - Recalculate a draft payslip twice (`recalculatePayslip`) → still one employee line, one employer line, and at most one `PASI_WARN` (Review Focus 5).
  - Unpaid leave pro-rating: basic prorated to 500 → share computed on 500.

- [ ] **Step 2: Run** `php artisan test --compact --filter=PasiCalculationTest` — expect FAIL.

- [ ] **Step 3: Implement** `PasiCalculator` and wire `PayrollCalculator::buildPayslip`: after the existing first and second passes, run a third pass over assignments whose component `calculation_type` is `PasiEmployee` or `PasiEmployer` (so the base sees every earning line). Resolve the rule once per payslip with `ruleFor($employee, $periodEnd)`; if there is at least one PASI assignment and the rule is null, create one line `{component_id: null, type: deduction, code: 'PASI_WARN', name: __('payroll::payslip.pasi_warning'), quantity: 1, rate: 0, amount: 0, sort_order: 950}`. Zero shares are skipped by the existing `$amount == 0.0` rule. Assignment-level `percent`/`amount` overrides are ignored for PASI components (the rule governs). Keep `gross_amount` updates as they are.

- [ ] **Step 4: Run** `php artisan test --compact --filter="PasiCalculationTest|PayrollPluginTest"` — expect PASS.

- [ ] **Step 5: Commit** `git add plugins/webkul/payroll tests/Feature/Payroll/PasiCalculationTest.php && git commit -m "Calculate PASI from dated rules with salary cap in payroll."`

---

### Task 4: PASI rules settings screen and component flag

**Files:**
- Create: `src/Filament/Resources/PasiRuleResource.php`, `Pages/ListPasiRules.php`, `Pages/CreatePasiRule.php`, `Pages/EditPasiRule.php`, `src/Policies/PasiRulePolicy.php`, `database/factories/PasiRuleFactory.php` (if not created in Task 1), `tests/Feature/Payroll/PasiResourceTest.php`
- Modify: `src/PayrollServiceProvider.php` (policy + `shield:generate` list), `src/Filament/Resources/SalaryComponentResource.php`, lang `payroll.php` (en, ar)

**Interfaces:**
- Consumes: `PasiRuleManager::addRule` (Task 2).
- Produces: `PasiRuleResource` in the `Configuration` cluster (slug `pasi-rules`, navigation sort 2), policy permissions mirroring `SalaryComponentPolicy` with the resource key `payroll_pasi::rule` (confirm the generated keys with `php artisan shield:generate --resource=PasiRuleResource --panel=admin`); a Toggle `is_pasi_insurable` on the component form.

- [ ] **Step 1: Write `PasiResourceTest.php`** using `livewire(...)`: list page shows rules; `CreatePasiRule` with category, rates, cap and `effective_from` closes the previous open rule (assert both rows) and notifies; create with an overlapping start shows a form error on `effective_from`; edit page can change a rate on a closed or open rule but rejects an overlap; a user without `view_any_payroll_pasi::rule` gets 403 on the list; the component form saves `is_pasi_insurable`.

- [ ] **Step 2: Run** `php artisan test --compact --filter=PasiResourceTest` — expect FAIL.

- [ ] **Step 3: Implement** the resource (labels through `TranslatesResourceLabels` like `SalaryComponentResource`, form with category select from `PasiCategory`, two rate inputs `numeric()->minValue(0)->maxValue(100)`, cap `numeric()->minValue(0)` nullable, `effective_from` required `DatePicker`; `effective_to` shown disabled on edit), `CreatePasiRule::handleRecordCreation(array $data): Model` delegating to `PasiRuleManager`, no `DeleteAction` and no bulk delete, table columns category/rates/cap/from/to with the current rule badge. Register the policy and add `PasiRuleResource` to the install `shield:generate` list. Add the toggle to the component form under `is_taxable`. Add en/ar strings (`models.pasi_rule`, `models_plural.pasi_rule`, `fields.employee_rate`, `employer_rate`, `salary_cap`, `effective_from`, `effective_to`, `is_pasi_insurable`).

- [ ] **Step 4: Run** `php artisan test --compact --filter="PasiResourceTest|TranslationKeysTest|PayrollArabicLabelsTest"` — expect PASS.

- [ ] **Step 5: Commit** `git add plugins/webkul/payroll tests/Feature/Payroll/PasiResourceTest.php && git commit -m "Add PASI rules settings and insurable flag on salary components."`

---

### Task 5: PASI contribution schedule report

**Files:**
- Create: `src/Services/PasiScheduleBuilder.php`, `src/Filament/Pages/PasiContributionSchedule.php`, `resources/views/filament/pages/pasi-contribution-schedule.blade.php`, `tests/Feature/Payroll/PasiScheduleTest.php`
- Modify: lang `payroll.php` (en, ar)

**Interfaces:**
- Consumes: `PasiCalculator`, `PasiRuleResolver` (Tasks 2–3); `PayslipStatus::Validated|Paid`; `Utf8Csv::fromRows(array $rows): string`.
- Produces: `PasiScheduleBuilder::rows(int $year, int $month): Collection` of arrays `{employee, civil_id, category, insurable_salary, employee_share, employer_share}` and `PasiScheduleBuilder::totals(Collection $rows): array{insurable_salary: float, employee_share: float, employer_share: float}`; `PasiScheduleBuilder::csv(int $year, int $month): string`. Page slug `payroll/pasi-schedule`, `HasPageShield`, navigation group = payroll group, sort 51.

- [ ] **Step 1: Write `PasiScheduleTest.php`:** with one validated Omani payslip, one paid expat payslip with a 1% employer rule, and one draft payslip: rows contain only validated/paid payslips; shares equal the sum of that payslip's lines whose component is `pasi_employee`/`pasi_employer`; insurable salary is the capped base; totals equal column sums; a month with no payslips returns an empty collection and zero totals; `csv()` starts with the UTF-8 BOM, has a header row and one row per employee with 3-decimal amounts; the page renders for a permitted user and 403s otherwise.

- [ ] **Step 2: Run** `php artisan test --compact --filter=PasiScheduleTest` — expect FAIL.

- [ ] **Step 3: Implement** the builder (query payslips for the period with `lines.component` eager loaded; skip employees with no PASI lines; category from `PasiRuleResolver::categoryFor`) and the page: a year/month form like `PayrollReports`, a table/blade listing of the rows with totals, and a header action "Export CSV" returning `response()->streamDownload(...)` of `csv()` named `pasi-schedule-{year}-{month}.csv`. Add en/ar strings.

- [ ] **Step 4: Run** `php artisan test --compact --filter=PasiScheduleTest` — expect PASS.

- [ ] **Step 5: Commit** `git add plugins/webkul/payroll tests/Feature/Payroll/PasiScheduleTest.php && git commit -m "Add PASI monthly contribution schedule with CSV export."`

---

### Task 6: Verification and hand-over notes

**Files:**
- Modify: `docs/qa/09-final-phase-readiness.md` (append a short "PASI rules" note under the production checklist)

- [ ] **Step 1:** Run `vendor/bin/pint --dirty --format agent`, then `php artisan test --compact --filter="Pasi|PayrollPluginTest|PayrollArabicLabelsTest|TranslationKeysTest"` — expect all PASS.
- [ ] **Step 2:** In the browser (Arabic locale, QA database): open Payroll → Configuration → PASI rules, add a new Omani rate effective next month, confirm the old row closes; generate a payslip for an Omani and an expat employee and confirm the lines; open the contribution schedule and export the CSV. Report what was seen.
- [ ] **Step 3:** Append to the QA ledger: run `php artisan migrate` and `php artisan shield:generate --resource=PasiRuleResource --panel=admin` on existing sites; expat employees now pay no employee PASI; the default rates are unverified and must be confirmed by the customer's accountant.
- [ ] **Step 4: Commit** `git add docs/qa/09-final-phase-readiness.md && git commit -m "Document PASI rules rollout steps."`
