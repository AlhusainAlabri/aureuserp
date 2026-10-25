# QA Ledger — Payroll: end-of-service gratuity

Date: 2026-10-26 · Spec: `docs/superpowers/specs/2026-10-25-payroll-gratuity-design.md` · Env: local QA DB, locale `ar`.

| ID | Sev | Finding | Status |
|----|-----|---------|--------|
| GR-01 | G | Leaving employees had no end-of-service calculation. | **done** — dated gratuity tiers, gratuity settings, a settlement per employee (gratuity, unused-leave payout, loan offset, final-month salary, editable adjustment), approval flow, mark-as-paid, Arabic statement PDF |
| GR-02 | V | Verified in the browser (Arabic): "تسوية نهاية الخدمة" action on a departed employee's page → draft settlement page with the per-tier breakdown (6 years + 233 days: 45 + 109.15 days × 800/30 = 4,110.685), final salary 800 × 20/31 = 516.129, net 4,626.814; the statement PDF renders right-to-left with shaped Arabic. Test data was removed afterwards. | verified |
| GR-03 | note | The default tiers (15 days a year for the first 3 years, 30 days after, pro-rated by days), the minimum service (1 year) and the daily-rate divisor (30) are **not verified against Omani labour law**. Confirm with the customer's accountant: Payroll → Configuration → Gratuity tiers / Gratuity settings. | open (customer decision) |
| GR-04 | note | Eligibility: only the category chosen in the settings (default expatriates, decided by nationality vs the company country — the same rule as PASI). Omani and nationality-less employees, departure reasons with "pays gratuity" switched off, and service below the minimum get gratuity 0 with the reason printed; leave payout and final salary are still owed. Set "Pays end-of-service gratuity" off on departure reasons that forfeit it (Employees → Configuration → Departure reasons). | by design |
| GR-05 | note | The service start is the earliest `first_joining_date` of the employee's contracts (else the earliest contract start date). Existing employees whose contracts have no joining date will be refused with a clear message until it is entered. | action before first use |
| GR-06 | note | Final-month salary is the **basic only**, pro-rated by calendar day of the departure month, and is 0 when that month already has a validated or paid payslip. HR can edit it (and the leave days and an adjustment with a note) while the settlement is a draft. | by design |
| GR-07 | note | Leave payout covers paid leave types that require an allocation (allocations approved up to the departure date minus approved leaves), valued at basic ÷ divisor per day. | by design |
| GR-08 | note | The loan offset is fixed when the settlement is built, together with the list of loans it covered. Paying clears only those loans, and **refuses** ("loans or payslip changed — cancel and create a new one") if the active loans or their balances differ, or if the departure month's payslip was validated or paid after the draft was built. A negative net (loans larger than what is owed) is shown and not blocked. | by design |
| GR-11 | note | **Departure date = last working day, counted as service.** Service is measured up to and including that day (joined 1 Jan 2020, last day 31 Dec 2022 = exactly 3 years) and the final month's salary includes it (day ÷ days in month). If your HR enters the first day *without* work instead, the gratuity is one day short and the final salary one day long. | **confirm with HR** |
| GR-12 | note | Only a draft can be sent to approval; a paid or cancelled settlement can never re-enter the workflow, and cancelling a settlement also cancels its pending approval. The database allows one live (non-cancelled) settlement per employee. | done (review findings) |
| GR-09 | note | Out of scope: excluding long unpaid leave from the service, notice-period pay, posting the settlement to accounting. | open (follow-ups) |
| GR-10 | note | Test infrastructure: after pulling new plugin migrations run `php artisan migrate --env=testing` once before the suite (see PS-08). | open (test hygiene) |

## Rollout on an existing site
1. `php artisan migrate --force` (tiers table with the two default tiers, settlements table, `pays_gratuity` on departure reasons, the three gratuity settings).
2. `php artisan shield:generate --resource=GratuityTierResource,EndOfServiceSettlementResource --page=ManageGratuitySettings --panel=admin --option=permissions` then `php artisan permissions:sync-extensions` (roles that can already update salary components get the new screens).
3. Review the tiers and settings with the accountant (GR-03), switch off "pays gratuity" on forfeiting departure reasons (GR-04), and make sure contracts carry a joining date (GR-05).
4. Optional: activate the "End-of-Service Settlement Approval" flow under Approvals; while no flow is active a draft settlement can be paid directly.
