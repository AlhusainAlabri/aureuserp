# QA Ledger — Payroll: PASI rules

Date: 2026-10-25 · Spec: `docs/superpowers/specs/2026-10-25-payroll-pasi-rules-design.md` · Env: local QA DB, locale `ar`.

| ID | Sev | Finding | Status |
|----|-----|---------|--------|
| PS-01 | G | PASI was a flat 7% / 11.5% of basic for everyone, with no nationality, cap or history. | **done** — dated rules per category (Omani / expat), salary cap, insurable flag on salary components; screens under Payroll → Configuration → PASI rules |
| PS-02 | V | Verified in the browser (Arabic): rules list, add-rate form (closes the previous rule the day before), edit page with the read-only "effective to"; contribution schedule page renders. | verified |
| PS-03 | note | An employee **without nationality** (or a company without a country, or no rule in force) gets no PASI and a zero `PASI_WARN` line on the payslip. The QA employee "محمد بن سالم الهنائي" had no nationality, so its draft payslip lost its PASI deduction after the upgrade. | **action before first payroll after upgrade** — fill in nationality for every employee; regenerate draft payslips and look for the warning line |
| PS-04 | note | Expat employees now pay no employee PASI (rule 0% / 0%) — the old flat 7% applied to everyone. Rates, cap and the insurable allowances are defaults that **must be confirmed by the customer's accountant** (Payroll → Configuration → PASI rules; "Counts toward PASI" on each allowance). | open (customer decision) |
| PS-05 | note | A per-employee percent or amount override on a PASI assignment is ignored; the rule governs. A customised flat percent on the seeded `PASI_EMP` / `PASI_EMR` components is carried into the Omani rule by the migration. | by design |
| PS-06 | note | Payroll batch references (`PAY-YYYY-MM`) are unique across companies, so two companies cannot both run payroll for the same month. Not caused by PASI; found while testing. | open (pre-existing) |
| PS-07 | note | The contribution schedule reads the insurable salary stored on each payslip (`pasi_base`, set when PASI is calculated), so a later change of cap, rate or "Counts toward PASI" flag never rewrites past months. Payslips from before the upgrade have no stored base and fall back to today's rule. A salary cap of 0 is refused (it would silently remove all PASI); leave the cap empty for no cap. | done (review findings I-1, I-2) |
| PS-08 | note | Test infrastructure: the first test run after a new plugin migration runs `migrate` inside the test's transaction, MySQL commits implicitly, and that test's rows stay in `nodhum_erp_test` (a leaked payroll batch then makes later payroll tests fail with a duplicate `PAY-YYYY-MM`). Run `php artisan migrate --env=testing` after pulling new plugin migrations, before the suite. | open (test hygiene) |

## Rollout on an existing site
1. `php artisan migrate --force` (creates `payroll_pasi_rules` with the default Omani and expat rules, adds `is_pasi_insurable`, switches `PASI_EMP` / `PASI_EMR` to the new calculation types).
2. `php artisan shield:generate --resource=PasiRuleResource --page=PasiContributionSchedule --panel=admin --option=permissions` then `php artisan permissions:sync-extensions` (roles that can already update salary components get the PASI rules and the contribution schedule).
3. Set nationality on every employee (see PS-03), review the rules and the "Counts toward PASI" flags with the accountant (PS-04), then regenerate draft payslips.
