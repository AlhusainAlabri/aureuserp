<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\Hr\EmployeeSalaryRaise;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Schema;

class RecentRaisesWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 6;

    protected static bool $isLazy = false;

    public function getColumnSpan(): int|string|array
    {
        return ['default' => 12, 'lg' => 3];
    }

    protected function getTableHeading(): string|Htmlable|null
    {
        return __('hr-extensions::widgets.recent_raises');
    }

    public function table(Table $table): Table
    {
        if (! Schema::hasTable('employee_salary_raises')) {
            return $table
                ->query(EmployeeSalaryRaise::query()->whereRaw('1 = 0'))
                ->columns([TextColumn::make('effective_date')])
                ->emptyStateHeading(__('dashboard.plugin_not_installed'));
        }

        $quarterStart = now()->firstOfQuarter();
        $quarterEnd = now()->lastOfQuarter();

        return $table
            ->query(
                EmployeeSalaryRaise::query()
                    ->with(['employee'])
                    ->whereBetween('effective_date', [$quarterStart, $quarterEnd])
                    ->orderByDesc('effective_date')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('employee.name')
                    ->label(__('dashboard.table.name'))
                    ->limit(30),
                TextColumn::make('effective_date')
                    ->label(__('hr-extensions::salary_raise.fields.effective_date'))
                    ->date(),
                TextColumn::make('raise_amount')
                    ->label(__('hr-extensions::salary_raise.fields.raise_amount'))
                    ->formatStateUsing(fn (?string $state): string => 'OMR '.number_format((float) $state, 3))
                    ->color(fn (?string $state): string => (float) $state >= 0 ? 'success' : 'danger'),
                TextColumn::make('raise_percent')
                    ->label(__('hr-extensions::salary_raise.fields.raise_percent'))
                    ->formatStateUsing(fn (?string $state): string => number_format((float) $state, 2).'%'),
                TextColumn::make('reason')
                    ->label(__('hr-extensions::salary_raise.fields.reason'))
                    ->badge(),
            ])
            ->emptyStateHeading(__('dashboard.all_caught_up'))
            ->emptyStateIcon('heroicon-o-banknotes')
            ->paginated(false);
    }
}
