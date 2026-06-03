<?php

namespace Webkul\Payroll\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Webkul\Employee\Models\Department;
use Webkul\Payroll\Filament\Resources\SalaryComponentResource;
use Webkul\Payroll\Models\Payslip;

class PayrollReports extends Page implements HasForms, HasTable
{
    use HasPageShield, InteractsWithForms, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 50;

    protected string $view = 'payroll::filament.pages.payroll-reports';

    protected static ?string $slug = 'payroll/reports';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'year'       => now()->year,
            'month'      => now()->month,
            'department' => null,
        ]);
    }

    public static function getNavigationLabel(): string
    {
        return __('payroll::payroll.reports.navigation');
    }

    public static function getNavigationGroup(): string
    {
        return __('payroll::payroll.navigation.group');
    }

    public function getTitle(): string
    {
        return __('payroll::payroll.reports.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('payroll::payroll.reports.summary'))
                    ->schema([
                        Select::make('year')
                            ->label(__('payroll::payroll.filters.year'))
                            ->options(collect(range(now()->year - 5, now()->year + 1))
                                ->mapWithKeys(fn (int $year): array => [$year => (string) $year])
                                ->all())
                            ->default(now()->year)
                            ->live(),
                        Select::make('month')
                            ->label(__('payroll::payroll.filters.month'))
                            ->options(__('payroll::payroll.months'))
                            ->default(now()->month)
                            ->live(),
                        Select::make('department')
                            ->label(__('payroll::payroll.filters.department'))
                            ->options(fn (): array => Department::query()->pluck('name', 'id')->all())
                            ->searchable()
                            ->placeholder(__('payroll::payroll.filters.all'))
                            ->live(),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    #[Computed]
    public function summary(): array
    {
        $query = $this->basePayslipQuery();

        return [
            'payslip_count'    => (clone $query)->count(),
            'employee_count'   => (clone $query)->distinct('employee_id')->count('employee_id'),
            'total_gross'      => (float) ((clone $query)->sum('gross_amount') ?? 0),
            'total_deductions' => (float) ((clone $query)->sum('deductions_amount') ?? 0),
            'total_net'        => (float) ((clone $query)->sum('net_amount') ?? 0),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Department::query()
                ->select([
                    'employees_departments.id',
                    'employees_departments.name',
                    DB::raw('COUNT(payroll_payslips.id) as payslip_count'),
                    DB::raw('COALESCE(SUM(payroll_payslips.gross_amount), 0) as total_gross'),
                    DB::raw('COALESCE(SUM(payroll_payslips.net_amount), 0) as total_net'),
                ])
                ->leftJoin('employees_employees', 'employees_employees.department_id', '=', 'employees_departments.id')
                ->leftJoin('payroll_payslips', function ($join): void {
                    $join->on('payroll_payslips.employee_id', '=', 'employees_employees.id')
                        ->where('payroll_payslips.period_year', '=', $this->data['year'] ?? now()->year)
                        ->where('payroll_payslips.period_month', '=', $this->data['month'] ?? now()->month);
                })
                ->when($this->data['department'] ?? null, fn ($query, $departmentId) => $query->where('employees_departments.id', $departmentId))
                ->groupBy('employees_departments.id', 'employees_departments.name'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('payroll::payroll.fields.department')),
                TextColumn::make('payslip_count')
                    ->label(__('payroll::payroll.reports.payslip_count')),
                TextColumn::make('total_gross')
                    ->label(__('payroll::payroll.reports.total_gross'))
                    ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
                TextColumn::make('total_net')
                    ->label(__('payroll::payroll.reports.total_net'))
                    ->formatStateUsing(fn (?string $state): string => SalaryComponentResource::formatMoney((float) ($state ?? 0))),
            ])
            ->paginated(false);
    }

    public function formatMoney(float $amount): string
    {
        return SalaryComponentResource::formatMoney($amount);
    }

    protected function basePayslipQuery(): Builder
    {
        return Payslip::query()
            ->when($this->data['year'] ?? null, fn ($query, $year) => $query->where('period_year', $year))
            ->when($this->data['month'] ?? null, fn ($query, $month) => $query->where('period_month', $month))
            ->when($this->data['department'] ?? null, fn ($query, $departmentId) => $query->whereHas(
                'employee',
                fn ($employeeQuery) => $employeeQuery->where('department_id', $departmentId),
            ));
    }
}
