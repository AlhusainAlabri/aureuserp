# Payroll End-of-Service Gratuity Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** When an employee leaves, HR creates an end-of-service settlement (gratuity, unused-leave payout, loan offset, final-month salary) that is approved through the approval engine, marked paid, and printed as an Arabic statement.

**Architecture:** Dated gratuity tiers plus spatie `GratuitySettings` drive a `GratuityCalculator`; `FinalSettlementBuilder` assembles an `EndOfServiceSettlement` (approval workflow like `Loan`); Filament screens live in the payroll plugin (tiers, settings, settlements) with one action on the employee page; the PDF reuses `pdf.trade-document`.

**Tech Stack:** PHP 8.4, Laravel 13, Filament 5, Pest 4, MySQL (`nodhum_erp_test`, shared and transactional), spatie/laravel-settings, wezlo/filament-approval, Dompdf.

**Spec:** `docs/superpowers/specs/2026-10-25-payroll-gratuity-design.md`

## Global Constraints
- Default tiers (editable, **unverified against Omani law**): years 0–3 at 15 days of basic per year; 3 and above at 30 days per year; `effective_from` `2000-01-01`. Settings defaults: `minimum_service_years` 1, `eligible_category` `expat`, `leave_payout_divisor` 30.
- Daily rate = last basic ÷ `leave_payout_divisor` (one divisor for gratuity, leave payout and nothing else). Money rounds to 3 decimals.
- Service years = whole calendar years from the start date plus remaining days ÷ 365 (3 calendar years is exactly 3.0 even across a leap day). Service start = earliest `first_joining_date` over the employee's `employee_contracts`, else earliest `start_date`; end = `employees_employees.departure_date`.
- Eligibility uses `PasiRuleResolver::categoryFor` (PASI plan): category must equal `eligible_category`; unknown nationality is ineligible. A departure reason with `pays_gratuity = false` is ineligible. A missing departure reason is eligible.
- An ineligible employee still gets a settlement: gratuity 0 with the reason stored in the breakdown; leave payout and final salary are still owed.
- One non-cancelled settlement per employee. Net may be negative (loan offset larger than what is owed); it is shown, not blocked.
- Plugin code in `plugins/webkul/payroll` (`Webkul\Payroll`); tests in `tests/Feature/Payroll/`; strings in both `resources/lang/en` and `resources/lang/ar` (`TranslationKeysTest` must stay green); new migrations are listed in `PayrollServiceProvider::hasMigrations` (settings migration in `hasSettings`).
- Permission keys are not guessed: read them with `FilamentShield::getResourcePermissions(Resource::class)` / `getPages()` and pin them with a test (the PASI work found them to be `*_payroll_<model>` style); extend `ExtensionPermissionsSync::syncPayroll` with the new keys.
- After adding a plugin migration, run `php artisan migrate --env=testing` once **before** the tests (first test run otherwise commits rows into the test DB).
- The working tree has unrelated uncommitted files: always `git add` explicit paths, never `-A`/`.`. `vendor/bin/pint --dirty --format agent` before each commit; no new dependencies; curly braces, explicit types.
- Implement on branch `feature/payroll-gratuity`, which already exists (created from `master`, with this plan committed on it).

## Review Focus
1. Exactly 3 calendar years including a leap day pays 45 days (no stray second-tier days); 3 years + 1 day pays 45 + 30/365 days (Task 2).
2. Missing joining date, missing departure date, or no contract: clear message, no settlement row; unknown nationality: settlement with gratuity 0 and a reason (Tasks 2, 3).
3. Departure in a month whose payslip is already validated or paid: final salary 0, no double pay (Task 3).
4. Loan offset larger than the total owed: negative net shown, loans still settled on payment; paying twice or editing after payment is refused (Task 3).
5. A second settlement is refused while one is live, allowed after cancelling; cancel is refused once paid (Task 3).

## File Structure
- Create (plugin `plugins/webkul/payroll`): migrations `2026_10_26_200000_create_payroll_gratuity_tiers_table.php`, `2026_10_26_200001_create_payroll_end_of_service_settlements_table.php`, `2026_10_26_200002_add_pays_gratuity_to_employees_departure_reasons_table.php`; settings migration `database/settings/2026_10_26_200003_create_gratuity_settings.php`; `src/Settings/GratuitySettings.php`; `src/Enums/SettlementStatus.php`; `src/Models/GratuityTier.php`, `EndOfServiceSettlement.php`; `database/factories/GratuityTierFactory.php`; `src/Services/GratuityDefaults.php`, `ServicePeriodResolver.php`, `BasicSalaryResolver.php`, `LeaveBalanceResolver.php`, `GratuityCalculator.php`, `GratuityResult.php`, `FinalSettlementBuilder.php`; `database/seeders/EndOfServiceApprovalFlowSeeder.php`; policies `GratuityTierPolicy.php`, `EndOfServiceSettlementPolicy.php`; Filament `GratuityTierResource` (+ pages), `EndOfServiceSettlementResource` (+ `ListEndOfServiceSettlements`, `ViewEndOfServiceSettlement`, `EditEndOfServiceSettlement`), `Pages/ManageGratuitySettings.php`, `Actions/CreateEndOfServiceSettlementAction.php`.
- Create (app): `app/Support/Pdf/Documents/EndOfServiceSettlementDocument.php`.
- Modify: `config/settings.php` (register `GratuitySettings`), `PayrollServiceProvider.php`, `plugins/webkul/employees/src/Models/DepartureReason.php` and its resource form, `app/Filament/Resources/EmployeeResource/Pages/ViewEmployee.php`, `app/Support/ExtensionPermissionsSync.php`, lang files `resources/lang/{en,ar}/{payroll,enums,exceptions,seeders}.php`.
- Tests: `tests/Helpers/GratuityFixtures.php`, `tests/Feature/Payroll/Gratuity{Schema,Calculation,Settlement,Resources,Pdf}Test.php`; docs `docs/qa/11-payroll-gratuity.md`.

---

### Task 1: Schema, settings, models, defaults

**Files:** the three migrations + settings migration, `GratuitySettings`, `SettlementStatus`, `GratuityTier` (+factory), `EndOfServiceSettlement` (model only: fillable, casts, relations, soft deletes), `GratuityDefaults`, `GratuityFixtures`, `GratuitySchemaTest`; modify `config/settings.php`, `PayrollServiceProvider`, `DepartureReason` model.

**Interfaces:**
- Produces: `SettlementStatus` (`Draft`, `PendingApproval`, `Approved`, `Paid`, `Cancelled`; `HasLabel`, `HasColor`); `GratuityTier` (table `payroll_gratuity_tiers`: `from_year` decimal(5,2), `to_year` decimal(5,2) nullable, `days_per_year` decimal(5,2), `effective_from` date, `effective_to` date nullable, `creator_id`; scope `inForce(CarbonInterface $date)`); `GratuitySettings` (group `gratuity`: `float $minimum_service_years`, `string $eligible_category`, `int $leave_payout_divisor`); `GratuityDefaults::seedTiers(): void` (idempotent, 2 rows); `EndOfServiceSettlement` (table and columns exactly as the spec's Data section, plus `employee()`, `company()`, `approvedBy()` relations; `status` cast `SettlementStatus`; `gratuity_breakdown` cast `array`); `employees_departure_reasons.pays_gratuity` boolean default true (guarded by `hasTable`/`hasColumn`); `Tests\Helpers\GratuityFixtures::employee(Company $company, ?string $nationality, string $joined, ?string $departed, float $basic = 900, ?bool $paysGratuity = null): Employee` (company/nationality through `PasiFixtures`; creates one `EmployeeContract` with `first_joining_date` = `$joined`; sets `departure_date`; optional `DepartureReason`), `tiers(): void` (clears and seeds defaults).

- [ ] **Step 1: Write `GratuitySchemaTest.php`** (`FeatureTestBootstrap::bootstrap(['payroll' => 'payroll_salary_components'])`, `app(HrExtensionSchemaService::class)->ensure()` in `beforeEach` so `employee_contracts` exists): `seedTiers()` creates `0→3 @15` and `3→null @30` from `2000-01-01`, twice leaves 2 rows; `GratuitySettings` resolves `1.0 / 'expat' / 30`; `payroll_end_of_service_settlements` has every spec column; a `DepartureReason` created without the field has `pays_gratuity` true and the flag is mass-assignable; `SettlementStatus` labels exist in both locales.
- [ ] **Step 2: Run** `php artisan test --compact --filter=GratuitySchemaTest` — expect FAIL (classes missing).
- [ ] **Step 3: Implement** the migrations (tiers migration ends with `GratuityDefaults::seedTiers()`; settlements table uses `foreignId` to `employees_employees`/`companies`, soft deletes, index `(employee_id, status)`), the settings migration (`$this->migrator->add('gratuity.minimum_service_years', 1.0)` etc., modelled on `plugins/webkul/projects/database/settings/2024_12_16_094021_create_project_task_settings.php`), register `GratuitySettings` in `config/settings.php`, and in the provider add the migrations to `hasMigrations`, `->hasSettings([...])->runsSettings()` (see `ProjectServiceProvider`), Add `pays_gratuity` to `DepartureReason::$fillable` and a boolean cast. Add en/ar labels for `settlement_status`.
- [ ] **Step 4:** `php artisan migrate --env=testing`, then run `php artisan test --compact --filter="GratuitySchemaTest|PayrollPluginTest|PasiSchemaTest"` — expect PASS.
- [ ] **Step 5: Commit** `git add plugins/webkul/payroll plugins/webkul/employees/src/Models/DepartureReason.php config/settings.php tests/Helpers/GratuityFixtures.php tests/Feature/Payroll/GratuitySchemaTest.php`, message "Add gratuity tiers, settlement table, settings and defaults."

---

### Task 2: Service period, basic salary and gratuity calculation

**Files:** `ServicePeriodResolver`, `BasicSalaryResolver`, `GratuityCalculator`, `GratuityResult`, `GratuityTier` overlap validation, `GratuityCalculationTest`; modify lang `payroll.php`.

**Interfaces:**
- Consumes: Task 1 models/settings/fixtures; `PasiRuleResolver::categoryFor(Employee): ?PasiCategory`.
- Produces: `ServicePeriodResolver::forEmployee(Employee): ?array{start: Carbon, end: Carbon}` (null when either date is missing or the employee has no contracts); `BasicSalaryResolver::forDate(Employee, CarbonInterface): float` (BASIC `EmployeeComponent` in force on the date: assignment `amount`, else component `default_amount`, else 0); `GratuityResult` (readonly: `float $amount`, `array $breakdown` of `{from, to, years, days, amount}` rows, `?string $ineligibleReason` one of `not_eligible_category|nationality_unknown|reason_does_not_pay|below_minimum_service`, `float $serviceYears`, `int $serviceDays`); `GratuityCalculator::calculate(Employee $employee, CarbonInterface $start, CarbonInterface $end): GratuityResult`; `GratuityTier` throws `ValidationException` (key `from_year`) on `saving` when its band overlaps another tier whose effective window overlaps its own.

- [ ] **Step 1: Write `GratuityCalculationTest.php`** (basic 900, so daily rate 30; tiers via `GratuityFixtures::tiers()`): exactly 3 calendar years `2020-01-01→2023-01-01` = `45 × 30 = 1350.000`, and `2019-01-01→2022-01-01` (contains no leap day) the same; a range containing 29 Feb, `2020-01-01→2023-01-01` already does, still 1350 (Review Focus 1); 3 years + 1 day = `(45 + 30/365) × 30`; 5 years = `(45 + 60) × 30 = 3150`; partial `2020-01-01→2022-07-01` = `round((2 + 181/365) × 15 × 30, 3)`; breakdown rows per tier sum to the amount; below 1 year → amount 0, reason `below_minimum_service`; Omani, null nationality, and `pays_gratuity = false` → 0 with their reasons; a missing departure reason is eligible; changing `GratuitySettings` (`minimum_service_years = 2`, `leave_payout_divisor = 26`) changes the result; a tier set that changes on a later `effective_from` applies by departure date; `ServicePeriodResolver`: earliest `first_joining_date` over two contracts, fallback to earliest `start_date`, null without contracts or without a departure date; `BasicSalaryResolver`: assignment amount, ended assignment ignored, 0 without BASIC; `GratuityTier` overlap: overlapping band in the same window throws, same band in a later window is allowed.
- [ ] **Step 2: Run** `php artisan test --compact --filter=GratuityCalculationTest` — expect FAIL.
- [ ] **Step 3: Implement** per the signatures above. Years = `$start->diffInYears($end)` floored (whole) plus `($end - ($start + wholeYears))` days ÷ 365; tiers = `GratuityTier::inForce($end)` ordered by `from_year`; per tier `yearsInTier = max(0, min(years, to ?? ∞) − from)`, `days = yearsInTier × days_per_year`, `amount = round(days × basic ÷ divisor, 3)`; total = sum of rounded tier amounts. Eligibility checks run in the order category → reason → minimum service.
- [ ] **Step 4: Run** `php artisan test --compact --filter="GratuityCalculationTest|GratuitySchemaTest|TranslationKeysTest"` — expect PASS.
- [ ] **Step 5: Commit** `git add plugins/webkul/payroll tests/Feature/Payroll/GratuityCalculationTest.php` — "Calculate end-of-service gratuity from dated tiers and settings."

---

### Task 3: Final settlement builder and lifecycle

**Files:** `LeaveBalanceResolver`, `FinalSettlementBuilder`, `EndOfServiceApprovalFlowSeeder`, `EndOfServiceSettlement` lifecycle methods, `GratuitySettlementTest`; modify `PayrollServiceProvider` (seeder), lang `exceptions.php`, `seeders.php` (en, ar).

**Interfaces:**
- Consumes: Task 2 services.
- Produces: `LeaveBalanceResolver::unusedDays(Employee, CarbonInterface $asOf): float` (0 when `time_off_leave_allocations` is absent; otherwise, over leave types with `requires_allocation = 'yes'` and `unpaid = false`: sum of allocations in state `validate_two` with `date_from <= $asOf` minus sum of leaves in `validate_one|validate_two`, floored at 0, rounded to 1 decimal); `FinalSettlementBuilder::build(Employee $employee): EndOfServiceSettlement` (throws `RuntimeException` with a translated message for: no departure date, no joining date, a live settlement already exists; creates a `draft` settlement whose `gratuity_breakdown` is `{reason: ?string, service_years: float, rows: [{from, to, years, days, amount}]}`); on `EndOfServiceSettlement`: `recalculateNet(): void` (`net = gratuity + leave_payout + final_salary + other_adjustment − loan_offset`, run on every `saving`; `leave_payout_amount = unused_leave_days × last_basic ÷ divisor` is recomputed when `unused_leave_days` changes), `isEditable(): bool` (draft only), `canBePaid(): bool` (not paid/cancelled and (no active approval flow or fully approved)), `markAsPaid(): void` (throws `RuntimeException` unless `canBePaid`; sets `paid` and `paid_at`; completes every `Active` loan of the employee used in the offset: `amount_repaid = total_amount`, `amount_remaining = 0`, status `Completed`, scheduled installments `Cancelled`), `cancel(): void` (draft/pending/approved only); approval hooks `onApprovalSubmitted/Approved/Rejected` setting `PendingApproval` / `Approved` (+ `approved_by`, `approved_at`) / `Draft` exactly like `Loan`; uses `HasApprovalWorkflow` with `isApproved` aliased to `isWorkflowApproved`.
- Final salary = `round(last_basic × departure_date->day ÷ departure_date->daysInMonth, 3)`, or 0 when the employee has a `validated`/`paid` payslip for the departure month. (Pro-rating by calendar day instead of reusing `PayrollCalculator`, which would create a batch and payslip as a side effect; HR can edit the amount while draft.)

- [ ] **Step 1: Write `GratuitySettlementTest.php`:** build for an expat (2020-01-01→2023-01-01, basic 900) gives gratuity 1350, last basic 900, service days, breakdown stored, status draft; final salary for a departure on 2023-01-16 = `900 × 16/31`; a paid payslip for 2023-01 makes it 0 (Review Focus 3); unused leave days from one allocation of 21 and leaves of 6 = 15 → payout `15 × 900/30 = 450`; payout 0 and no error when the Time Off tables are absent (skip the case when they exist, or assert `LeaveBalanceResolver` returns 0 through a guarded partial); active loans with remaining 200 and 150 → offset 350 and net = gratuity + payout + final salary − 350; offset larger than everything → negative net, builder succeeds (Review Focus 4); ineligible (Omani) → gratuity 0, reason in breakdown, leave and final salary still present; refusals with messages: no departure date, no contract/joining date (no row created, Review Focus 2), second settlement while one is live, allowed after `cancel()` (Review Focus 5); approval: with an active flow `canBePaid()` is false until approved and `markAsPaid()` throws, with no active flow it succeeds; flow built as in `PayrollPluginTest` (`ApprovalFlow` + one `UserResolver` step; define a helper named `gratuityFlow` in this file); `markAsPaid()` completes the offset loans and cancels their scheduled installments; paying twice and cancelling a paid settlement throw; editing `unused_leave_days` recomputes payout and net; `EndOfServiceApprovalFlowSeeder` creates one inactive flow, twice leaves one.
- [ ] **Step 2: Run** `php artisan test --compact --filter=GratuitySettlementTest` — expect FAIL.
- [ ] **Step 3: Implement** per the signatures and rules above; the seeder mirrors `LoanApprovalFlowSeeder` (inactive; steps HR manager, finance manager, general manager) and is registered in the provider's `hasSeeders`. Translations: `exceptions.settlement_no_departure_date`, `settlement_no_joining_date`, `settlement_exists`, `settlement_cannot_be_paid`, `settlement_cannot_be_cancelled`; seeder flow name and step names.
- [ ] **Step 4: Run** `php artisan test --compact --filter="GratuitySettlementTest|GratuityCalculationTest|PayrollPluginTest|TranslationKeysTest"` — expect PASS.
- [ ] **Step 5: Commit** `git add plugins/webkul/payroll tests/Feature/Payroll/GratuitySettlementTest.php` — "Build end-of-service settlements with approval, loan offset and payment."

---

### Task 4: Configuration screens (tiers, settings, departure reason flag)

**Files:** `GratuityTierResource` (+ `ListGratuityTiers`, `CreateGratuityTier`, `EditGratuityTier`), `GratuityTierPolicy`, `Pages/ManageGratuitySettings.php`, `GratuityResourcesTest`; modify `PayrollServiceProvider` (policy, `shield:generate` list), `DepartureReasonResource` form, lang `payroll.php`.

**Interfaces:**
- Consumes: Task 1 and 2 models; `Configuration` cluster.
- Produces: `GratuityTierResource` (slug `gratuity-tiers`, cluster `Configuration`, no delete, form `from_year`, `to_year`, `days_per_year`, `effective_from`, `effective_to`); `ManageGratuitySettings` (`SettingsPage` for `GratuitySettings` in the payroll `Configuration` cluster, `HasPageShield`; fields `minimum_service_years`, `eligible_category` select from `PasiCategory`, `leave_payout_divisor` integer ≥ 1); a `Toggle` `pays_gratuity` on `DepartureReasonResource` visible only when `Schema::hasColumn('employees_departure_reasons', 'pays_gratuity')`.

- [ ] **Step 1: Write `GratuityResourcesTest.php`** (`Livewire::test`, `Filament::setCurrentPanel(Filament::getPanel('admin'))`, permissions via `Permission::findOrCreate` + `forgetCachedPermissions`, as in `PasiResourceTest`): tiers list shows rows for a permitted user and 403s otherwise; creating a tier saves; an overlapping band shows a form error on `from_year`; `ManageGratuitySettings` saves changed values and rejects divisor 0; the departure reason form saves `pays_gratuity = false`; policy keys equal `FilamentShield::getResourcePermissions(GratuityTierResource::class)` and the page key equals the one in `FilamentShield::getPages()`.
- [ ] **Step 2: Run** `php artisan test --compact --filter=GratuityResourcesTest` — expect FAIL.
- [ ] **Step 3: Implement** following `PasiRuleResource` (labels via `__('payroll::payroll.models…')`, pages remap every `ValidationException` key to `data.<key>`), settings page modelled on `ManageTasks` in the projects plugin, policy without `delete`, provider registration. Add en/ar strings.
- [ ] **Step 4: Run** `php artisan test --compact --filter="GratuityResourcesTest|PasiResourceTest|TranslationKeysTest"` — expect PASS.
- [ ] **Step 5: Commit** `git add plugins/webkul/payroll plugins/webkul/employees/src/Filament tests/Feature/Payroll/GratuityResourcesTest.php` — "Add gratuity tier and settings screens and the departure reason flag."

---

### Task 5: Settlement screens, employee action, permissions

**Files:** `EndOfServiceSettlementResource` (+ list/view/edit pages), `EndOfServiceSettlementPolicy`, `CreateEndOfServiceSettlementAction`, `GratuitySettlementResourceTest`; modify `app/Filament/Resources/EmployeeResource/Pages/ViewEmployee.php`, `app/Support/ExtensionPermissionsSync.php`, provider, lang.

**Interfaces:**
- Consumes: Task 3 model and builder; `App\Filament\Traits\HasApprovalActions` (see `ViewPayrollBatch`); `ChatterAction`.
- Produces: `EndOfServiceSettlementResource` (slug `end-of-service-settlements`, payroll navigation group like `LoanResource`; table with employee, departure date, gratuity, net, status badge, approval status column; view page with header actions: approval actions, "Mark as paid" (calls `markAsPaid`, notifies on `RuntimeException`), "Cancel", "Download statement" (Task 6)); edit page only while `isEditable()` (fields `unused_leave_days`, `final_salary_amount`, `other_adjustment`, `adjustment_note`); policy: `viewAny/view/create`, `update` and `cancel` require the update permission **and** `isEditable()` / non-paid, `markPaid` requires the update permission; `CreateEndOfServiceSettlementAction` (visible when the employee has `departure_date`, no live settlement, and the user may `create`; runs `FinalSettlementBuilder`, shows builder errors as a danger notification, redirects to the new view page) added to `ViewEmployee` header actions behind `class_exists` and `Schema::hasTable('payroll_end_of_service_settlements')`; `syncPayroll()` also grants the settlement permissions, the tier permissions and the settings page permission to roles that can update salary components.

- [ ] **Step 1: Write `GratuitySettlementResourceTest.php`:** list shows settlements for a permitted user, 403 otherwise; the employee action creates a draft settlement and redirects to its view page, is hidden without a departure date or when a live settlement exists, and shows the builder's message when the joining date is missing; edit page changes `unused_leave_days` (payout and net follow) while draft and is forbidden after approval; "Mark as paid" refuses with an active unapproved flow (notification) and succeeds otherwise, after which edit and cancel are forbidden; permission keys equal Shield's list; `syncPayroll()` gives a role with `update_payroll_salary::component` every new key and leaves a viewer-only role without them, twice.
- [ ] **Step 2: Run** `php artisan test --compact --filter=GratuitySettlementResourceTest` — expect FAIL.
- [ ] **Step 3: Implement** per the interface block (patterns: `LoanResource`, `ViewPayrollBatch`, `PasiRulePolicy`, `CloseEmployeeFileAction`). Add `EndOfServiceSettlementResource` to the install `shield:generate` list.
- [ ] **Step 4: Run** `php artisan test --compact --filter="GratuitySettlementResourceTest|PasiPermissionsSyncTest|HrExtensionsTest|HrGapImplementationTest"` — expect PASS.
- [ ] **Step 5: Commit** `git add plugins/webkul/payroll app/Filament/Resources/EmployeeResource/Pages/ViewEmployee.php app/Support/ExtensionPermissionsSync.php tests/Feature/Payroll/GratuitySettlementResourceTest.php` — "Add settlement screens, employee action and permissions."

---

### Task 6: Arabic settlement statement (PDF)

**Files:** `app/Support/Pdf/Documents/EndOfServiceSettlementDocument.php`, `GratuityPdfTest`; modify `ViewEndOfServiceSettlement` (download action), lang `payroll.php`.

**Interfaces:**
- Consumes: Task 3 model; `DocumentMoney`, `OmrFormatter`, the document array contract of `AccountMoveDocument::make` (keys `company`, `title`, `partyLabel`, `party`, `meta`, `columns`, `rows`, `totals`, `notes`, `closing`, optional `tables`).
- Produces: `EndOfServiceSettlementDocument::make(EndOfServiceSettlement $settlement): array` (party `null`; `meta` rows: employee, civil ID, joining date, departure date, service length in years/days; main table = gratuity breakdown per tier (band, years, days, amount) with an "ineligible" note when `gratuity_breakdown.reason` is set; `totals` = gratuity, leave payout, final salary, adjustment, loan offset (negative), net with class `grand`; `notes` = the adjustment note when present); header action "Download statement" on the view page streaming `Pdf::loadView('pdf.trade-document', ['docs' => [document]])->output()` as `end-of-service-<id>.pdf`.

- [ ] **Step 1: Write `GratuityPdfTest.php`:** with locale `ar` the PDF output starts with `%PDF`; the document array contains the per-tier rows and a net total equal to `$settlement->net_amount` formatted by `DocumentMoney`; an ineligible settlement renders the reason note and a zero gratuity; a negative net renders; the download action returns a file for a permitted user (`assertFileDownloaded`), and is not visible without the view permission. Render the PDF to PNG with `pymupdf` once during Task 7 to check the Arabic layout.
- [ ] **Step 2: Run** `php artisan test --compact --filter=GratuityPdfTest` — expect FAIL.
- [ ] **Step 3: Implement** the document class and the action; add en/ar strings under `payroll::settlement.pdf.*`.
- [ ] **Step 4: Run** `php artisan test --compact --filter="GratuityPdfTest|TradeDocumentsTest|TranslationKeysTest"` — expect PASS.
- [ ] **Step 5: Commit** `git add app/Support/Pdf/Documents/EndOfServiceSettlementDocument.php plugins/webkul/payroll tests/Feature/Payroll/GratuityPdfTest.php` — "Add the Arabic end-of-service settlement statement."

---

### Task 7: Verification and rollout notes

**Files:** `docs/qa/11-payroll-gratuity.md` (new; do not edit other QA files).

- [ ] **Step 1:** `vendor/bin/pint --dirty --format agent`, then `php artisan test tests --compact` in the background (about 10–15 minutes; do not run other tests meanwhile) — expect 0 failures.
- [ ] **Step 2:** Apply `php artisan migrate` to the local QA database, run `php artisan shield:generate --resource=GratuityTierResource,EndOfServiceSettlementResource --page=ManageGratuitySettings --panel=admin --option=permissions` and `php artisan permissions:sync-extensions`; in the browser (Arabic) set a departure date on a QA expat employee with a contract, create the settlement from the employee page, edit leave days, download the PDF and render it to PNG (`pymupdf`) to check RTL, then remove the test data. Report what was seen.
- [ ] **Step 3:** Write the QA note: rollout commands, the unverified default tiers and settings, that Omani and nationality-less employees get gratuity 0, that the final salary is basic only and pro-rated by calendar day (edit while draft), that leave payout covers paid leave types that require allocation, and that unpaid-leave exclusion, notice pay and accounting posting are out of scope.
- [ ] **Step 4: Commit** `git add docs/qa/11-payroll-gratuity.md` — "Document the gratuity rollout."
