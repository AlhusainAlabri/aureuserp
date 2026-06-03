<?php

namespace Webkul\Purchase\Filament\Admin\Pages;

use App\Filament\Extensions\PurchaseOrderResourceExtensions;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Webkul\Employee\Models\Department;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Models\Order;

class DepartmentExpenseReport extends Page implements HasForms, HasTable
{
    use HasPageShield, InteractsWithForms, InteractsWithTable;

    protected string $view = 'purchases::filament.admin.pages.department-expense-report';

    protected static ?string $slug = 'purchases/department-report';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 99;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'month'      => now()->month,
            'year'       => now()->year,
            'department' => null,
        ]);
    }

    public static function getNavigationLabel(): string
    {
        return __('purchases::filament/admin/pages/department-expense-report.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('purchases::filament/admin/pages/department-expense-report.navigation.group');
    }

    public function getTitle(): string
    {
        return __('purchases::filament/admin/pages/department-expense-report.navigation.title');
    }

    protected static function getPagePermission(): ?string
    {
        return 'page_purchases_department_report';
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make()
                ->schema([
                    Select::make('month')
                        ->label(__('purchases::filament/admin/pages/department-expense-report.form.fields.month'))
                        ->options(PurchaseOrderResourceExtensions::departmentReportMonthOptions())
                        ->default(now()->month)
                        ->live(),
                    Select::make('year')
                        ->label(__('purchases::filament/admin/pages/department-expense-report.form.fields.year'))
                        ->options(array_combine(
                            range(now()->year - 5, now()->year),
                            range(now()->year - 5, now()->year)
                        ))
                        ->default(now()->year)
                        ->live(),
                    Select::make('department')
                        ->label(__('purchases::filament/admin/pages/department-expense-report.form.fields.department'))
                        ->options(Department::pluck('name', 'id'))
                        ->searchable()
                        ->placeholder(__('purchases::filament/admin/pages/department-expense-report.form.fields.all-departments'))
                        ->live(),
                ])
                ->columns(3),
        ];
    }

    #[Computed]
    public function summaryData(): array
    {
        $query = $this->baseQuery();

        return [
            'total_purchases'      => (clone $query)->count(),
            'total_amount'         => (clone $query)->sum('total_amount') ?? 0,
            'missing_receipts'     => (clone $query)
                ->where('receipt_uploaded', false)
                ->count(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                return Department::query()
                    ->select([
                        'employees_departments.id',
                        'employees_departments.name',
                        DB::raw('COUNT(DISTINCT purchases_orders.id) as purchases_count'),
                        DB::raw('COALESCE(SUM(purchases_orders.total_amount), 0) as total_amount'),
                        DB::raw('COUNT(DISTINCT CASE WHEN purchases_orders.receipt_uploaded = 1 THEN purchases_orders.id END) as receipts_uploaded'),
                        DB::raw('COUNT(DISTINCT CASE WHEN purchases_orders.receipt_uploaded = 0 THEN purchases_orders.id END) as receipts_missing'),
                    ])
                    ->leftJoin('purchases_orders', function ($join) {
                        $join->on('employees_departments.id', '=', 'purchases_orders.requesting_department_id')
                            ->whereIn('purchases_orders.state', [OrderState::PURCHASE->value, OrderState::DONE->value]);
                    })
                    ->when($this->data['month'] ?? null, function ($query, $month) {
                        $query->whereMonth('purchases_orders.approved_at', $month);
                    })
                    ->when($this->data['year'] ?? null, function ($query, $year) {
                        $query->whereYear('purchases_orders.approved_at', $year);
                    })
                    ->when($this->data['department'] ?? null, function ($query, $department) {
                        $query->where('employees_departments.id', $department);
                    })
                    ->groupBy('employees_departments.id', 'employees_departments.name');
            })
            ->columns([
                TextColumn::make('name')
                    ->label(__('purchases::filament/admin/pages/department-expense-report.table.columns.department'))
                    ->searchable(),
                TextColumn::make('purchases_count')
                    ->label(__('purchases::filament/admin/pages/department-expense-report.table.columns.purchases-count'))
                    ->numeric(),
                TextColumn::make('total_amount')
                    ->label(__('purchases::filament/admin/pages/department-expense-report.table.columns.total-amount'))
                    ->formatStateUsing(fn ($state): string => 'ر.ع. '.number_format((float) $state, 3))
                    ->numeric(),
                TextColumn::make('receipts_uploaded')
                    ->label(__('purchases::filament/admin/pages/department-expense-report.table.columns.receipts-uploaded'))
                    ->numeric(),
                TextColumn::make('receipts_missing')
                    ->label(__('purchases::filament/admin/pages/department-expense-report.table.columns.receipts-missing'))
                    ->numeric()
                    ->color('danger'),
            ])
            ->filters([])
            ->actions([])
            ->headerActions([
                Action::make('export')
                    ->label(__('purchases::filament/admin/pages/department-expense-report.actions.export-csv'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        return $this->exportCsv();
                    }),
            ]);
    }

    protected function baseQuery()
    {
        return Order::query()
            ->whereIn('state', [OrderState::PURCHASE->value, OrderState::DONE->value])
            ->when($this->data['month'] ?? null, function ($query, $month) {
                $query->whereMonth('approved_at', $month);
            })
            ->when($this->data['year'] ?? null, function ($query, $year) {
                $query->whereYear('approved_at', $year);
            })
            ->when($this->data['department'] ?? null, function ($query, $department) {
                $query->where('requesting_department_id', $department);
            });
    }

    public function exportCsv()
    {
        $data = $this->table->getRecords()->map(function ($record) {
            return [
                'department'        => $record->name,
                'purchases_count'   => $record->purchases_count,
                'total_amount'      => $record->total_amount,
                'receipts_uploaded' => $record->receipts_uploaded,
                'receipts_missing'  => $record->receipts_missing,
            ];
        });

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="department-expense-report-'.now()->format('Y-m-d').'.csv"',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Department', 'Purchases Count', 'Total Amount', 'Receipts Uploaded', 'Receipts Missing']);
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
