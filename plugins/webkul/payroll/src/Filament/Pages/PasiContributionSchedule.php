<?php

namespace Webkul\Payroll\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\Payroll\Filament\Resources\SalaryComponentResource;
use Webkul\Payroll\Services\PasiScheduleBuilder;
use Webkul\Payroll\Support\PayrollCalendar;

class PasiContributionSchedule extends Page implements HasForms
{
    use HasPageShield, InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?int $navigationSort = 51;

    protected string $view = 'payroll::filament.pages.pasi-contribution-schedule';

    protected static ?string $slug = 'payroll/pasi-schedule';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'year'  => now()->year,
            'month' => now()->month,
        ]);
    }

    public static function getNavigationLabel(): string
    {
        return __('payroll::payroll.pasi.schedule.navigation');
    }

    public static function getNavigationGroup(): string
    {
        return __('payroll::payroll.navigation.group');
    }

    public function getTitle(): string
    {
        return __('payroll::payroll.pasi.schedule.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('payroll::payroll.pasi.schedule.period'))
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
                            ->options(fn (): array => PayrollCalendar::monthOptions())
                            ->default(now()->month)
                            ->live(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label(__('payroll::payroll.pasi.schedule.export'))
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn (): StreamedResponse => $this->downloadCsv()),
        ];
    }

    #[Computed]
    public function rows(): Collection
    {
        return app(PasiScheduleBuilder::class)->rows($this->selectedYear(), $this->selectedMonth());
    }

    /**
     * @return array{insurable_salary: float, employee_share: float, employer_share: float}
     */
    #[Computed]
    public function totals(): array
    {
        return app(PasiScheduleBuilder::class)->totals($this->rows);
    }

    public function formatMoney(float $amount): string
    {
        return SalaryComponentResource::formatMoney($amount);
    }

    protected function downloadCsv(): StreamedResponse
    {
        $year = $this->selectedYear();
        $month = $this->selectedMonth();
        $csv = app(PasiScheduleBuilder::class)->csv($year, $month);

        return response()->streamDownload(
            function () use ($csv): void {
                echo $csv;
            },
            sprintf('pasi-schedule-%d-%02d.csv', $year, $month),
            ['Content-Type' => 'text/csv'],
        );
    }

    protected function selectedYear(): int
    {
        return (int) ($this->data['year'] ?? now()->year);
    }

    protected function selectedMonth(): int
    {
        return (int) ($this->data['month'] ?? now()->month);
    }
}
