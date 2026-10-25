# Audit log and accounting period lock Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A central, read-only audit log of who changed what (reusing Chatter messages) and a per-company accounting lock date that blocks changes to posted entries in closed periods.

**Architecture:** Everything lives in the `app/` layer (no plugin file is edited). An `AuditRecorder` attaches model listeners to a configured list of extra models and writes `chatter_messages` rows (`log_name = 'audit'`); a read-only Filament resource lists them. A `PeriodLock` service plus a guard registered from a service provider enforce the lock date on `Move` (and subclasses), `MoveLine`, `Payment` and `BankStatement`, with a permission override that writes an audit entry.

**Tech Stack:** Laravel 13, Filament 5, Livewire 4, Pest 4 (Livewire tests), Filament Shield permissions, MySQL test DB `nodhum_erp_test`.

**Spec:** `docs/superpowers/specs/2026-10-25-audit-log-and-period-lock-design.md`

## Global Constraints
- No plugin file under `plugins/` is edited; wiring is in `app/` and `bootstrap/providers.php` (new provider `App\Providers\AuditServiceProvider`).
- Audit entries are `Webkul\Chatter\Models\Message` rows: `type = 'notification'`, `log_name = 'audit'`, `event` set, `properties = { "<label>": { type: added|modified|removed, old_value, new_value } }`. Event names reuse Chatter's: `created`, `updated`, `soft_deleted`, `hard_deleted`, `restored`, `deleted`; new ones: `login`, `login_failed`, `lock_override`, `lock_date_changed`, `permissions_changed`.
- No entry is written when nobody is signed in (`Filament::auth()->user() ?? Auth::user()`), except the explicit-causer cases `login`, `login_failed`. A recorder failure is `report()`ed and never reaches the caller.
- Sensitive attributes (`password`, `remember_token`, two-factor secret/recovery codes) are logged as changed with `old_value`/`new_value` = `'***'`. Ignored attributes: `created_at`, `updated_at`, `deleted_at`, `remember_token` (as noise), `sort`.
- Lock rule: a change is locked when (original date ≤ lock date and original state is not `draft`) or (new date ≤ lock date and new state is not `draft`). Lock date null (or no company) means unlocked. The lock date cannot be set later than today.
- Reconciliation-only saves are allowed; all other dirty attributes on guarded models are locked. Guard runs on `creating`, `updating`, `deleting` (after the model's own `saving` hooks), and with no signed-in user the guard still applies and no override exists.
- Permissions: `view_any_audit_log`, `view_audit_log` (Admin, super_admin, general_manager); `page_manage_lock_date`, `post_in_locked_period` (Admin, super_admin, finance_manager). Keys are fixed by reading `FilamentShield::getPages()` after the page exists, not guessed.
- English and Arabic lang files in `lang/en/audit.php` and `lang/ar/audit.php`; `tests/Feature/TranslationKeysTest.php` must stay green.
- Run `vendor/bin/pint --dirty --format agent` before each commit; commit messages end with `Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>`.

## Decisions that refine the spec
- The spec's "Contract" does not exist; salary data is `Webkul\Payroll\Models\EmployeeComponent`, which replaces it in the coverage list.
- The lock date is written with a query (`PeriodLock::setLockDate`) and its change is recorded explicitly as `lock_date_changed`, so the plugin `Company` model needs no edit (the spec assumed it would log automatically).
- The guard sits on `updating` rather than `saving` so it sees the final dirty set after the models' own computed fields.

## Review Focus
1. Paying a locked-period invoice with a payment dated after the lock date still succeeds (reconciliation allowance) — pinned in Task 5.
2. Editing a posted `Invoice`, `Bill`, `CreditNote` or `Refund` in the locked period is refused (events are per concrete class) — Task 5.
3. Marking a chatter log row as read (`is_read` update) and recorder failures must not break the user's save — Tasks 1, 2.
4. A batch that creates hundreds of payslip lines stays fast and writes entries without failing — Task 1.
5. A move or statement with no company, or a company with no lock date, never throws and is never locked — Tasks 4, 5.

---

### Task 1: Audit recorder and coverage list

**Files:**
- Create: `app/Support/Audit/AuditedModels.php`, `app/Support/Audit/AuditRecorder.php`, `app/Providers/AuditServiceProvider.php`, `lang/en/audit.php`, `lang/ar/audit.php`
- Modify: `bootstrap/providers.php` (add `AuditServiceProvider::class` next to the other `App\Providers` entries)
- Test: `tests/Feature/Audit/AuditRecorderTest.php`

**Interfaces:**
- Produces: `AuditedModels::classes(): array<int, class-string<Model>>` — the list below filtered by `class_exists` and by "does not use `Webkul\Chatter\Traits\HasLogActivity`". `AuditRecorder::register(): void` (idempotent, called from the provider's `boot`). `AuditRecorder::record(Model $subject, string $event, array $changes, ?Authenticatable $causer = null): ?Message` — public, used by Tasks 2, 5, 6; skips (returns null) when there is no causer and nobody is signed in; never throws.
- Coverage list: payroll `Payslip`, `PayslipLine`, `SalaryComponent`, `PasiRule`, `GratuityTier`, `EndOfServiceSettlement`, `EmployeeComponent` (all `Webkul\Payroll\Models`); `Webkul\Attendance\Models\{AttendanceDay, OvertimeRequest, ImportBatch}`; `Webkul\Account\Models\{BankStatement, BankStatementLine, Journal, Account, Tax}`; `Webkul\Security\Models\{User, Role}`.

- [ ] **Step 1: Write the failing tests** in `AuditRecorderTest.php` (sign in a factory user first; use `Message::query()->where('log_name', 'audit')`):
  - `it writes created updated and deleted entries with user old and new values` (use `SalaryComponent` or `PasiRule` factory: update a field, assert the entry's `causer_id`, `event = 'updated'`, `properties` old/new, `messageable_type/id`; delete → `hard_deleted` or `soft_deleted` according to soft-delete support).
  - `it masks sensitive attributes` (update a `User`'s password: both values `'***'`).
  - `it writes nothing without a signed-in user`.
  - `it does not log a model that already uses HasLogActivity twice` (assert `AuditedModels::classes()` contains none of the classes using the trait, e.g. `PayrollBatch`, `Loan`).
  - `it never breaks the save when writing fails` (bind a Message creating-listener that throws; the model update still succeeds).
  - `it logs a large payslip batch quickly` (create 300 `PayslipLine` rows in a loop with a signed-in user; assert 300 entries and the loop finishes under 10 seconds).
- [ ] **Step 2: Run** `php artisan test --compact tests/Feature/Audit/AuditRecorderTest.php` — expect failures (classes missing).
- [ ] **Step 3: Implement** `AuditedModels`, `AuditRecorder` (register `created`, `updated`, `deleted` — with the `soft_deleted`/`hard_deleted` split via `SoftDeletes` — and `restored` on each class; build `properties` from `getChanges()`/`getOriginal()` minus ignored attributes; skip an `updated` event with no remaining changes; set `company_id` from the causer's default company like `HasChatter::addMessage` does) and `AuditServiceProvider` (calls `AuditRecorder::register()` in `boot`; guard against the Schema not being ready, as `ExtensionPermissionsSync` does with `PermissionTables::areReady()`). Add `lang/en|ar/audit.php` with event names and the title strings.
- [ ] **Step 4: Run** the same test file — expect all pass; run `php artisan test --compact tests/Feature/TranslationKeysTest.php`.
- [ ] **Step 5: Commit** `git add app/Support/Audit app/Providers/AuditServiceProvider.php bootstrap/providers.php lang/en/audit.php lang/ar/audit.php tests/Feature/Audit` — "Record changes to payroll, attendance, accounting setup and security records in the audit log."

### Task 2: Entry guard, sign-in events and role permission changes

**Files:**
- Create: `app/Support/Audit/AuditEntryGuard.php`, `app/Listeners/Audit/RecordSignIn.php`, `app/Models/Audit/AuditEntry.php`
- Modify: `app/Providers/AuditServiceProvider.php` (register the guard and `Event::listen` for `Illuminate\Auth\Events\Login` and `Failed`), `app/Overrides/Webkul/Security/Filament/Resources/RoleResource/Pages/EditRole.php` and `CreateRole.php` (call the recorder around `syncPermissionsByNames`)
- Test: `tests/Feature/Audit/AuditEntryProtectionTest.php`, `tests/Feature/Audit/AuditSignInAndRolesTest.php`

**Interfaces:**
- Consumes: `AuditRecorder::record(...)` from Task 1.
- Produces: `App\Models\Audit\AuditEntry extends Webkul\Chatter\Models\Message` with a global scope `whereNotNull('event')` (used by Task 3); `AuditEntryGuard::register(): void` (listens on `Message` and `AuditEntry` `updating`/`deleting`; refuses with `Illuminate\Auth\Access\AuthorizationException` when the row has an `event`, except an update whose dirty attributes are only `is_read`, `updated_at`); `AuditRecorder::recordRolePermissions(Role $role, array $before, array $after): void` (writes one `permissions_changed` entry with `Permissions added` / `Permissions removed` lists; no entry when equal).

- [ ] **Step 1: Write the failing tests:**
  - `it refuses to edit or delete an audit entry` and `it still lets a log row be marked as read` (update `is_read` on a row with an event succeeds; changing `body` or deleting throws).
  - `it leaves ordinary chatter messages (no event) editable and deletable`.
  - `it records a successful login on the user and a failed login for an existing user` (dispatch `Login`/`Failed` events with the `web` guard; entry `event = login|login_failed`, causer = that user) and `it records nothing for a failed login with no known user`.
  - `it records the permissions added and removed when a role is saved from the role pages` (Livewire test of the two override pages; assert the entry lists the added and removed names; saving without changes writes none).
- [ ] **Step 2: Run** the two files — expect failure.
- [ ] **Step 3: Implement** the guard, `AuditEntry`, `RecordSignIn` (uses `AuditRecorder::record` with an explicit causer), and the role-page calls (read `$this->record->permissions()->pluck('name')` before and after the existing `syncPermissionsByNames`). Search the repo for any code or Chatter UI action that deletes or edits messages with an `event`; if one exists, hide or guard that action instead of letting the guard throw into the user (note it in the commit message).
- [ ] **Step 4: Run** both files plus `tests/Feature/Security` — expect pass.
- [ ] **Step 5: Commit** — "Protect audit entries, record sign-ins and role permission changes."

### Task 3: Audit log screen

**Files:**
- Create: `app/Filament/Resources/AuditLogResource.php`, `app/Filament/Resources/AuditLogResource/Pages/ListAuditLogs.php`, `app/Policies/AuditEntryPolicy.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php` (add `AuditLogResource::class` to `->resources([...])`), `app/Support/ExtensionPermissionsSync.php` (new `syncAudit(): void` called from `syncAll()`; grants `view_any_audit_log`, `view_audit_log` to `Admin`, `super_admin`, `general_manager` through `grantPermissionsToRoles`), `lang/en/audit.php`, `lang/ar/audit.php`
- Test: `tests/Feature/Audit/AuditLogResourceTest.php`

**Interfaces:**
- Consumes: `AuditEntry` (Task 2). Produces: resource model `AuditEntry`; `AuditEntry::recordUrl(): ?string` (best-effort link to the subject's Filament page, `null` when the record is gone or has no page).
- Policy `AuditEntryPolicy` (`viewAny`/`view` check the two permissions; every other ability false). Follow `SystemBackupResource` for placement and the navigation group "Settings".

- [ ] **Step 1: Write the failing tests** (Livewire `ListAuditLogs`): `it lists entries newest first for a permitted user`, `it filters by user record type event and date range`, `it searches the summary text`, `it forbids a user without the permission` (HTTP 403 on `/admin/audit-logs` — confirm the URL from `php artisan route:list --path=audit`), `it shows no create edit or delete actions`, `it only shows entries of the user's allowed companies`, `it renders a deleted record's entry without a link`. Re-run `tests/Feature/Security/ResourcePolicyCoverageTest.php` unchanged.
- [ ] **Step 2: Run** — expect failure.
- [ ] **Step 3: Implement** the resource (list page only; columns when, who, record type (headline of the morph class basename), record link via `recordUrl()`, event label, readable old → new summary built from `properties`; filters and search per the spec; scope by `company_id` in `auth()->user()->allowedCompanies()`), the policy, the registration, the permission sync, and lang strings in both languages.
- [ ] **Step 4: Run** the new file, `ResourcePolicyCoverageTest.php`, `TranslationKeysTest.php` — expect pass.
- [ ] **Step 5: Commit** — "Add the read-only audit log screen and its permissions."

### Task 4: Lock date storage and rule

**Files:**
- Create: `database/migrations/2026_10_28_100000_add_accounting_lock_date_to_companies.php`, `app/Support/Accounting/PeriodLock.php`, `app/Exceptions/PeriodLockedException.php`
- Test: `tests/Feature/Accounting/PeriodLockRuleTest.php`

**Interfaces:**
- Produces: `PeriodLock::lockDateFor(?int $companyId): ?CarbonImmutable`; `PeriodLock::blocks(?int $companyId, mixed $originalDate, mixed $originalState, mixed $newDate, mixed $newState): bool` (pure rule from Global Constraints; accepts dates as strings/Carbon/null and states as strings or backed enums; a null date is treated as "no date" and never locks); `PeriodLock::setLockDate(int $companyId, ?CarbonInterface $date, Authenticatable $by): void` (rejects a date after today with `ValidationException`, updates through a query, records `lock_date_changed` via `AuditRecorder::record($company, ..., causer: $by)`, clears the memo); `PeriodLock::forget(): void` (clears the per-request memo); `PeriodLock::userMayOverride(?Authenticatable $user): bool` (`can('post_in_locked_period')`); `PeriodLockedException(string $message)` whose message names the lock date and is translated (`audit.lock.refused`).

- [ ] **Step 1: Write the failing tests:** table-driven `blocks` cases (posted in period → true; draft in period → false; draft→posted into the period → true; posted outside the period → false; posted moved into the period → true; lock date null → false; company null → false; boundary: date equal to the lock date is locked); `setLockDate` rejects tomorrow, stores today, writes one `lock_date_changed` entry with old and new date, and `lockDateFor` returns the new value after `forget()`.
- [ ] **Step 2: Run** `php artisan test --compact tests/Feature/Accounting/PeriodLockRuleTest.php` — expect failure; run `php artisan migrate --env=testing` first (new migration).
- [ ] **Step 3: Implement** the migration (nullable `date` column `accounting_lock_date` on `companies`, `down()` drops it), `PeriodLock`, the exception, and the lang strings.
- [ ] **Step 4: Run** the test file — expect pass.
- [ ] **Step 5: Commit** — "Add the accounting lock date and the rule that decides what it blocks."

### Task 5: Guard wiring, reconciliation allowance and override

**Files:**
- Create: `app/Support/Accounting/PeriodLockGuard.php`
- Modify: `app/Providers/AuditServiceProvider.php` (call `PeriodLockGuard::register()` from `boot`)
- Test: `tests/Feature/Accounting/PeriodLockGuardTest.php`

**Interfaces:**
- Consumes: `PeriodLock` (Task 4), `AuditRecorder::record` (Task 1).
- Produces: `PeriodLockGuard::register(): void`. Listeners on `creating`, `updating`, `deleting` for each of `Move`, `Invoice`, `Bill`, `CreditNote`, `Refund`, `MoveLine`, `Payment`, `BankStatement`, `BankStatementLine`. Judging: a Move/Payment/BankStatement by its own `date` and `state` (a `BankStatement` has no state, so any statement dated in the period counts as posted); a `MoveLine` by its parent `move`; a `BankStatementLine` by its parent `statement`. Reconciliation allowance (starting list, to be confirmed in Step 3): Move `payment_state`, `amount_residual`, `amount_residual_signed`; MoveLine `amount_residual`, `amount_residual_currency`, `reconciled`, `full_reconcile_id`, `matching_number`; Payment `is_reconciled`, `is_matched`, `is_sent`; plus `updated_at` everywhere. On refusal throw `PeriodLockedException`; on a permitted override call `AuditRecorder::record($subject, 'lock_override', [...])` once per request per subject and operation (a line is recorded on its parent move).

- [ ] **Step 1: Write the failing tests** (set the lock date with `PeriodLock::setLockDate`, call `PeriodLock::forget()` between cases):
  - `it refuses to edit reset cancel or delete a posted entry in the locked period` for `Move`, and again, looped, for `Invoice`, `Bill`, `CreditNote`, `Refund` factories.
  - `it refuses to change lines of a posted entry in the locked period` and `to post or move an entry into it`; `it allows a draft in the locked period but not posting it`.
  - `it lets a locked-period invoice be paid by a payment dated after the lock date` (build a posted invoice dated before the lock date, register the payment through `PaymentRegister`/`AccountManager` as the existing payment tests do — read `plugins/webkul/accounts/src/Models/PaymentRegister.php` and `AccountManager.php:290-350,1290-1330,1800-1830,1920-1935` to reproduce it — and assert `payment_state` becomes paid) and `it still blocks changing that invoice's amount or date`.
  - `it refuses payments and bank statements dated in the period`.
  - `it lets a user holding post_in_locked_period change a locked entry and writes one lock_override entry`; `it still blocks when nobody is signed in`.
  - `it never locks when the lock date is null or the record has no company`.
  - `it leaves entries after the lock date untouched`.
- [ ] **Step 2: Run** the file — expect failure.
- [ ] **Step 3: Implement** the guard. Before finalizing the allowlist, run the payment test with a temporary dump of `getDirty()` for each guarded model during reconciliation and set the allowlist to exactly the keys observed (record the observed keys in the commit message). Mirror how `guardApprovalBeforePosting` surfaces its error so Filament actions show a message instead of a 500; if the refusal still shows as a server error in a Livewire action test, add a `render` method on `PeriodLockedException` or a Livewire exception handler in `AuditServiceProvider` that sends a danger `Notification` and halts.
- [ ] **Step 4: Run** `PeriodLockGuardTest.php`, then `php artisan test --compact tests/Feature/Accounting tests/Feature/Invoices tests/Feature/Payroll` — expect pass (lock date null everywhere else).
- [ ] **Step 5: Commit** — "Block changes to posted accounting entries in a locked period."

### Task 6: Lock date page, permissions, QA note

**Files:**
- Create: `app/Filament/Pages/Settings/ManageLockDate.php`, `resources/views/filament/pages/settings/manage-lock-date.blade.php` (mirror `manage-notification-preferences`), `docs/qa/13-audit-log-and-period-lock.md`
- Modify: `app/Providers/Filament/AdminPanelProvider.php` (add to `->pages([...])`), `app/Support/ExtensionPermissionsSync.php` (`syncAccounting(): void` called from `syncAll()`: grants `page_manage_lock_date` and `post_in_locked_period` to `Admin`, `super_admin`, `finance_manager`), `lang/en/audit.php`, `lang/ar/audit.php`
- Test: `tests/Feature/Accounting/ManageLockDatePageTest.php`

**Interfaces:**
- Consumes: `PeriodLock::setLockDate`, `lockDateFor`.
- Produces: page `ManageLockDate` in the Accounting → Configuration cluster (`Webkul\Accounting\Filament\Clusters\Configuration`); form: company select (the user's allowed companies, default their default company) and a date picker (`maxDate` today, clearable); saving calls `PeriodLock::setLockDate`.

- [ ] **Step 1: Write the failing tests** (Livewire): `it saves the lock date for the chosen company and logs it`, `it rejects a future date`, `it can clear the date`, `it is forbidden without the page permission and open to Admin`, `it shows the saved date when the company changes`. Add one test to the same file: after `ExtensionPermissionsSync::syncAll()`, Admin holds `page_manage_lock_date`, `post_in_locked_period`, `view_any_audit_log`.
- [ ] **Step 2: Run** — expect failure.
- [ ] **Step 3: Implement** the page and the sync; after creating the page, read `FilamentShield::getPages()` and set the page-permission key in the sync to the generated name. Write `docs/qa/13-audit-log-and-period-lock.md` in the style of `docs/qa/12-attendance.md`: findings table (AL-01 audit log done, AL-02 lock date done, AL-03 audit-log limits: no tamper-evidence against database admins, system actions unlogged, PayslipLine volume, AL-04 lock covers accounting documents only, AL-05 reconciliation stays allowed, AL-06 finance must set the date after each close) and a rollout section: `php artisan migrate --force`, `php artisan permissions:sync-extensions`, set the lock date once per company, give accountants "post in locked period" deliberately.
- [ ] **Step 4: Run** the new file, then the full affected set: `php artisan test --compact tests/Feature/Audit tests/Feature/Accounting tests/Feature/Security tests/Feature/TranslationKeysTest.php` — expect pass.
- [ ] **Step 5: Commit** — "Add the lock date page, permission sync and QA note."
