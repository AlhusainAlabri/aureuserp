# QA Ledger — Phase 7: projects, timesheets, time off, payroll, recruitment

Date: 2026-10-25 · Env: local QA DB · locale `ar`.
Flows tested in the browser: project → task → timesheet entry · leave allocation (approve) → leave request (approve) · pay components → employee components → payroll batch → payslips → payslip PDF · candidate → job position → applicant → stage → create employee.

| ID | Sev | Finding | Status |
|----|-----|---------|--------|
| P7-01 | U | Time-off allocation view showed the raw `<p>` HTML of the notes. | **fixed** — notes rendered as HTML; empty notes show a dash |
| P7-02 | U | Every rich-text entry app-wide (notes, descriptions) could print raw tags. | **fixed** — text entries detect HTML-looking state and render it |
| P7-03 | G | Time-off view page lacked the employee and the status; substitute ("بديل") was required even in companies with one employee. | **fixed** — employee and status badge added; substitute is required only when the policy is on and there is more than one active employee |
| P7-04 | U | Time-off list showed dates as `2026-11-05 00:00:00`, the duration clipped ("4 يوم عمل (+ 1…"), empty description cells blank. | **fixed** — dates, wrapped duration, dash placeholder |
| P7-05 | F | Payslip and payroll-register PDF footers printed the literal `:page`, not the page number. | **fixed** — CSS page counter |
| P7-06 | U | Payroll "name" field label was the English key in Arabic. | **fixed** |
| P7-07 | U | Recruitment resources' singular labels ("نوع نشاط", "منصب وظيفي") disagreed with the navigation wording; tests failing. | **fixed** — definite forms |
| P7-08 | G | A new applicant had no pipeline stage, so the stage ribbon highlighted nothing and stage moves had no starting point. | **fixed** — new applicants start in the default stage (lowest sort); test (skips when recruitment tables are not installed in the test DB) |
| P7-09 | U | Applicant actions "Refuse" / "Restore" were English; refuse modal used a bug icon. | **fixed** — all Filament action labels go through the translator; icon replaced |
| P7-10 | U | Timesheet form opened with empty date and employee. | **fixed** — defaults to today and the signed-in user |
| P7-11 | G | The "new applicant" modal (applicants have no `/create` route) asked only for the candidate, so the job and department stayed empty. | **fixed** — optional job position field; department follows the job |

## Known limitations / follow-ups
- Payroll components for an employee must be assigned before generating payslips (no warning when a payslip is empty).
