<?php

namespace Webkul\Payroll\Services;

use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Webkul\Account\AccountManager;
use Webkul\Account\Enums\DisplayType;
use Webkul\Account\Enums\MoveState;
use Webkul\Account\Enums\MoveType;
use Webkul\Account\Facades\Account as AccountFacade;
use Webkul\Account\Models\Move;
use Webkul\Account\Models\MoveLine;
use Webkul\Payroll\Enums\SalaryComponentType;
use Webkul\Payroll\Models\PayrollBatch;
use Webkul\Payroll\Models\PayslipLine;
use Webkul\Payroll\Models\SalaryComponent;

class PayrollAccountingService
{
    /**
     * @var array<string, SalaryComponent|null>
     */
    protected array $systemComponents = [];

    public function createDraftJournalEntry(PayrollBatch $batch): ?Move
    {
        if (! class_exists(Move::class)) {
            return null;
        }

        $batch->loadMissing(['payslips.lines.component', 'company', 'journal']);

        $this->systemComponents = [];

        $this->assertPostable($batch);

        $move = Move::query()->create([
            'move_type'   => MoveType::ENTRY,
            'state'       => MoveState::DRAFT,
            'date'        => $batch->pay_date,
            'journal_id'  => $batch->journal_id,
            'company_id'  => $batch->company_id,
            'reference'   => $batch->reference_number,
            'name'        => __('payroll::accounting.move_name', ['reference' => $batch->reference_number]),
            'creator_id'  => Auth::id() ?? $batch->creator_id,
        ]);

        $linePayloads = $this->buildMoveLines($batch);

        if (class_exists(MoveLine::class)) {
            foreach ($linePayloads as $linePayload) {
                MoveLine::query()->create($linePayload + [
                    'move_id'    => $move->id,
                    // PRODUCT keeps the account set here; a line without a display type is reset to the journal's default account on save.
                    'display_type' => DisplayType::PRODUCT,
                    'company_id'   => $batch->company_id,
                    'date'         => $batch->pay_date,
                    'creator_id'   => Auth::id() ?? $batch->creator_id,
                ]);
            }
        }

        if (class_exists(AccountFacade::class)) {
            AccountFacade::computeAccountMove($move);
        } elseif (class_exists(AccountManager::class)) {
            app(AccountManager::class)->computeAccountMove($move);
        }

        $batch->update([
            'account_move_id' => $move->id,
            'posted_at'       => now(),
        ]);

        return $move->fresh();
    }

    /**
     * The salary component a payslip line posts through: its own, else the system component with the same code
     * (lateness, overtime and loan lines are written by payroll and carry no component).
     */
    protected function componentFor(PayslipLine $line): ?SalaryComponent
    {
        if ($line->component !== null) {
            return $line->component;
        }

        return $this->systemComponents[$line->code] ??= SalaryComponent::query()->where('code', $line->code)->first();
    }

    /**
     * Refuses, with a message HR can act on, a batch whose entry could not be built or would not balance.
     *
     * @throws RuntimeException
     */
    protected function assertPostable(PayrollBatch $batch): void
    {
        if ($batch->journal === null) {
            throw new RuntimeException(__('payroll::exceptions.posting_no_journal'));
        }

        if ($batch->journal->default_account_id === null) {
            throw new RuntimeException(__('payroll::exceptions.posting_journal_no_account'));
        }

        $lines = $batch->payslips->flatMap->lines->filter(fn ($line): bool => (float) $line->amount > 0);

        $unmapped = $lines
            ->filter(fn ($line): bool => $this->componentFor($line)?->account_id === null)
            ->map(fn ($line): string => (string) $line->code)
            ->unique()
            ->values();

        if ($unmapped->isNotEmpty()) {
            throw new RuntimeException(__('payroll::exceptions.posting_missing_accounts', ['codes' => $unmapped->implode('، ')]));
        }

        $noLiability = $lines
            ->filter(fn ($line): bool => $line->type === SalaryComponentType::EmployerCost && $this->componentFor($line)?->liability_account_id === null)
            ->map(fn ($line): string => (string) $line->code)
            ->unique()
            ->values();

        if ($noLiability->isNotEmpty()) {
            throw new RuntimeException(__('payroll::exceptions.posting_missing_liability_accounts', ['codes' => $noLiability->implode('، ')]));
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildMoveLines(PayrollBatch $batch): array
    {
        $lines = [];
        $earningsTotal = 0.0;
        $deductionsTotal = 0.0;
        $employerCostTotal = 0.0;
        $netTotal = 0.0;

        foreach ($batch->payslips as $payslip) {
            foreach ($payslip->lines as $line) {
                $amount = (float) $line->amount;
                $description = __('payroll::accounting.line_description', [
                    'batch' => $batch->reference_number,
                    'code'  => $line->code,
                    'name'  => $line->name,
                ]);

                $component = $this->componentFor($line);
                $accountId = $component?->account_id;

                match ($line->type) {
                    SalaryComponentType::Earning      => $this->pushDebitLine($lines, $accountId, $amount, $description),
                    SalaryComponentType::Deduction    => $this->pushCreditLine($lines, $accountId, $amount, $description),
                    SalaryComponentType::EmployerCost => $this->pushEmployerCostLines($lines, $accountId, $component?->liability_account_id, $amount, $description),
                    default                           => null,
                };

                match ($line->type) {
                    SalaryComponentType::Earning      => $earningsTotal += $amount,
                    SalaryComponentType::Deduction    => $deductionsTotal += $amount,
                    SalaryComponentType::EmployerCost => $employerCostTotal += $amount,
                    default                           => null,
                };
            }

            $netTotal += (float) $payslip->net_amount;
        }

        $this->pushCreditLine(
            $lines,
            $batch->journal?->default_account_id,
            $netTotal,
            __('payroll::accounting.bank_credit', ['reference' => $batch->reference_number]),
        );

        return $lines;
    }

    /**
     * An employer cost is an expense (debit) owed to a third party such as the PASI fund (credit).
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    protected function pushEmployerCostLines(array &$lines, ?int $expenseAccountId, ?int $liabilityAccountId, float $amount, string $description): void
    {
        $this->pushDebitLine($lines, $expenseAccountId, $amount, $description);
        $this->pushCreditLine($lines, $liabilityAccountId, $amount, $description);
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    protected function pushDebitLine(array &$lines, ?int $accountId, float $amount, string $description): void
    {
        if ($amount <= 0) {
            return;
        }

        $lines[] = [
            'account_id' => $accountId,
            'name'       => $description,
            'debit'      => round($amount, 3),
            'credit'     => 0,
            'balance'    => round($amount, 3),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    protected function pushCreditLine(array &$lines, ?int $accountId, float $amount, string $description): void
    {
        if ($amount <= 0) {
            return;
        }

        $lines[] = [
            'account_id' => $accountId,
            'name'       => $description,
            'debit'      => 0,
            'credit'     => round($amount, 3),
            'balance'    => round(-$amount, 3),
        ];
    }
}
