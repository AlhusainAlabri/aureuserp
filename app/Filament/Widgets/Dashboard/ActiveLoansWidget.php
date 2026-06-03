<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Widgets\Dashboard\Concerns\HasOrgDashboardTableLayout;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Schema;
use Webkul\Payroll\Enums\LoanStatus;
use Webkul\Payroll\Models\Loan;

class ActiveLoansWidget extends BaseWidget
{
    use HasOrgDashboardTableLayout;

    protected static ?int $sort = 8;

    protected static ?string $heading = null;

    public static function canView(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('hr_manager')
            || $user->hasRole('finance_manager')
            || $user->hasRole('general_manager');
    }

    public function getTableHeading(): ?string
    {
        return __('dashboard.widgets.active_loans');
    }

    public function table(Table $table): Table
    {
        if (! Schema::hasTable('payroll_loans')) {
            return $table
                ->query(Loan::query()->whereRaw('1 = 0'))
                ->columns([
                    TextColumn::make('id')->label(__('dashboard.plugin_not_installed')),
                ]);
        }

        return $table
            ->query(
                Loan::query()
                    ->with('employee')
                    ->where('status', LoanStatus::Active)
                    ->orderByDesc('amount_remaining')
                    ->limit(10),
            )
            ->columns([
                TextColumn::make('reference_number')
                    ->label(__('payroll::payroll.loan.fields.reference'))
                    ->searchable(),
                TextColumn::make('employee.name')
                    ->label(__('payroll::payroll.loan.fields.employee')),
                TextColumn::make('total_amount')
                    ->label(__('payroll::payroll.loan.fields.total'))
                    ->formatStateUsing(fn ($state): string => 'ر.ع. '.number_format((float) $state, 3)),
                TextColumn::make('amount_remaining')
                    ->label(__('payroll::payroll.loan.fields.remaining'))
                    ->formatStateUsing(fn ($state): string => 'ر.ع. '.number_format((float) $state, 3)),
                TextColumn::make('progress')
                    ->label(__('payroll::payroll.loan.fields.progress'))
                    ->state(fn (Loan $record): string => $record->getProgressPercent().'%'),
            ])
            ->paginated(false);
    }
}
