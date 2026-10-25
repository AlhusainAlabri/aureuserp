# Audit log and accounting period lock — design

Date: 2026-10-25 · Status: draft for review · Two parts, one spec, built in this order: (1) audit log, (2) period lock. Part 2 writes into part 1 (lock overrides are audit entries).

## Goal
Make financial and HR records accountable. An owner or auditor can answer "who changed what, and when" from one screen, and nobody can alter a closed accounting period without leaving a trace.

## Today (verified in code, not run)
- The Chatter plugin (`HasLogActivity`) already writes "who changed which field from what to what" into `chatter_messages` for ~28 models (accounting moves, payments, employees, payroll batches, loans, orders, partners, companies). It is opt-in per model and per field (`getLogAttributeLabels`), shown only inside each record, skips changes with no signed-in user, and the rows can be edited or deleted like any message.
- Not covered: payslips and lines, contracts, salary components, PASI rules, gratuity tiers and settlements, attendance days and overtime requests, bank statements, journals/accounts/taxes, users/roles/permissions, logins.
- No lock date or fiscal-year logic exists. Posted entries can be edited, reset or deleted in any month by anyone with permission.
- `Move` is subclassed (`Invoice`, `Bill`, `CreditNote`, `Refund`, …); model events registered by `Move::boot()` run per concrete class, so anything attached from outside must cover every subclass.

## Assumptions (verify with the customer)
- Readers of the log are the owner, the external auditor and HR/finance managers; tamper-proof evidence against database administrators is not required (see Out of scope).
- A single lock date per company is enough; one stricter VAT lock date is a later extension.
- Existing sites keep working unchanged: a company with no lock date is never locked.

## Part 1 — Audit log

### Data
No new audit table. Entries are `chatter_messages` rows (`type = notification`, `event` set, `properties` = `{ "<label>": { type, old_value, new_value } }`, the shape `HasLogActivity` already writes), so the existing per-record timeline shows the new entries too. New `log_name = 'audit'` marks entries written by the new recorder.

### Components (all in the `app/` layer; no plugin file is edited)
- `App\Support\Audit\AuditedModels` — the list of extra model classes to cover, in one place. A class that already uses `HasLogActivity` is skipped (no duplicate entries).
- `App\Support\Audit\AuditRecorder` — attaches `created`, `updated`, `deleted`/`soft deleted`, `restored` listeners to each listed class (and writes the same message shape). Logs every dirty attribute except noise (`updated_at`, `created_at`, `remember_token`, `sort`); sensitive attributes (`password`, tokens) are logged as "changed" without values. Skips when nobody is signed in (same rule as Chatter). Never throws into the caller (report and continue).
- Initial coverage list: payroll `Payslip`, `PayslipLine`, `SalaryComponent`, `PasiRule`, `GratuityTier`, `EndOfServiceSettlement`; employees `Contract`; attendance `AttendanceDay`, `OvertimeRequest` (approver and manual HR edits visible), `AttendanceImportBatch`; accounting setup `BankStatement`, `BankStatementLine`, `Journal`, `Account`, `Tax`; security `User`, `Role` and role↔permission changes.
- Model names in this list are by domain; the plan fixes the exact classes by reading the plugins before the list is written.
- Sign-in events: a listener on `Login` and `Failed` writes an entry on the `User` (`event = login | login_failed`); a failed attempt is recorded only when the email belongs to an existing user.
- `App\Filament\Resources\AuditLogResource` (Settings group) — read-only list over `chatter_messages where event is not null`: when, who, record type (friendly name), link to the record when it still exists, event, readable "old → new" summary; filters by user, record type, event, date range; search; no create/edit/delete actions; scoped to the user's allowed companies. Permission from Shield (`view_any` only), granted to Admin/super_admin through `ExtensionPermissionsSync`.
- Immutability guard (registered from an `app/` service provider on `Message`): an update or delete of a message that has an `event` is refused through the model. Bulk query deletes and direct database access are not covered (documented limit).

### Errors and edge cases
- Recorder failure never blocks a save (report and continue).
- Records deleted later keep their entries; the link column shows the record type and id as plain text.
- Hard deletes of listed models are logged before the row disappears.

## Part 2 — Accounting period lock

### Data
- `companies.accounting_lock_date` (nullable date) — one app migration. The company already logs its changes, so moving the date is audited by existing code once the field has a label.

### Components
- `App\Support\Accounting\PeriodLock` — `lockDateFor(companyId)`, and a guard called from model events with the model and the operation. Rule: a change is **locked** when (the original date ≤ lock date and the original state is not draft) or (the new date ≤ lock date and the new state is not draft). So posted/cancelled documents in the period cannot be edited, reset, cancelled or deleted, and nothing can be posted or moved into the period; drafts may exist there.
- Guarded models: `Move` and every subclass, `MoveLine` (judged through its move), `Payment`, `BankStatement` and its lines. Listeners are registered from an `app/` service provider for each concrete class (verified by a test that loops the subclasses).
- Reconciliation allowance: a save whose dirty attributes are only reconciliation/payment-status fields (payment state, residual amounts, reconciled flag, matching/full-reconcile references) is allowed, so paying a locked-period invoice with a payment dated after the lock still works. The exact allowlist is fixed in the plan after reading what the reconcile and payment code writes, and pinned by tests; any other changed attribute (date, amounts, partner, account, journal, state, line add/remove) is locked.
- Override: a user holding "post in locked period" (new custom permission, granted to the accountant/Admin roles by `ExtensionPermissionsSync`) passes the guard; each override writes an audit entry `event = lock_override` on the record with the user and the lock date. With no signed-in user (scheduler, queue, console) the guard still applies and no override exists.
- Setting page: Accounting → Configuration → "Lock date" (Shield page permission "manage lock date"). The date cannot be later than today (typo guard); moving it backwards (unlocking) is allowed to the same permission and is recorded by the company's change log. Refusals show a translated message that names the lock date.
- Lang files: English and Arabic for page, messages and event names.

### Existing sites
Lock date is null, so nothing changes until an accountant sets it. After deploy: `php artisan migrate`, then `php artisan permissions:sync-extensions` (new permissions), then set the lock date once per company.

## Out of scope
- A tamper-evident audit store independent of the application database (option B), exports/prints/downloads in the log, scheduler/queue actions with no user.
- Payroll batches and inventory moves under the lock (payroll has its own validated/paid states); a separate tax lock date; fiscal-year closing entries; per-user or per-journal lock dates.
- Back-filling audit entries for history before this change.

## Testing (Pest, feature)
Audit: each listed model writes created/updated/deleted entries with user and old/new values; models already using the trait are not logged twice; password changes are masked; no entry without a user; recorder failure does not break the save; login and failed-login entries; the audit screen lists, filters and is permission-gated; messages with an event cannot be updated or deleted.
Lock: for `Move` and every subclass, `MoveLine`, `Payment`, `BankStatement`: edit / post / reset / cancel / delete in the locked period are refused; draft allowed in the period but not postable; posting or moving into the period refused; a document after the lock date untouched; reconciling a locked-period invoice with a later payment succeeds while changing its amount or date fails; override user passes and an audit entry exists; no user context is still locked; lock date null changes nothing; the page rejects future dates, is permission-gated, and moving the date is logged.
