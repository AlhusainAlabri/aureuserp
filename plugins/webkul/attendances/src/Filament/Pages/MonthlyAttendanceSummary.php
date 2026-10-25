<?php

namespace Webkul\Attendance\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\Attendance\Services\AttendanceMonthlyReport;

class MonthlyAttendanceSummary extends Page implements HasForms
{
    use HasPageShield, InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'attendances/monthly-summary';

    protected string $view = 'attendances::filament.pages.monthly-attendance-summary';

    /**
     * @var array<string, mixed>|null
     */
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
        return __('attendances::attendances.monthly.navigation');
    }

    public static function getNavigationGroup(): string
    {
        return __('attendances::attendances.navigation.group');
    }

    public function getTitle(): string
    {
        return __('attendances::attendances.monthly.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('attendances::attendances.monthly.period'))
                    ->schema([
                        Select::make('year')
                            ->label(__('attendances::attendances.monthly.year'))
                            ->options(collect(range(now()->year - 5, now()->year + 1))
                                ->mapWithKeys(fn (int $year): array => [$year => (string) $year])
                                ->all())
                            ->default(now()->year)
                            ->selectablePlaceholder(false)
                            ->live(),
                        Select::make('month')
                            ->label(__('attendances::attendances.monthly.month'))
                            ->options(fn (): array => $this->monthOptions())
                            ->default(now()->month)
                            ->selectablePlaceholder(false)
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
                ->label(__('attendances::attendances.monthly.export'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (): StreamedResponse => $this->downloadCsv()),
        ];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, totals: array<string, mixed>, truncated: bool, totalEmployees: int}
     */
    #[Computed]
    public function report(): array
    {
        return app(AttendanceMonthlyReport::class)->build(Auth::user(), $this->selectedYear(), $this->selectedMonth());
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function rows(): Collection
    {
        return collect($this->report['rows']);
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function totals(): array
    {
        return $this->report['totals'];
    }

    /**
     * "×1.25: 1.50 h · ×1.50: 1.00 h" for the per-rate split of the overtime hours.
     *
     * @param  array<string, float>  $byMultiplier
     */
    public function formatBreakdown(array $byMultiplier): string
    {
        return collect($byMultiplier)
            ->map(fn (float $hours, string $multiplier): string => sprintf('×%s: %s', $multiplier, number_format($hours, 2, '.', '')))
            ->implode(' · ');
    }

    protected function downloadCsv(): StreamedResponse
    {
        $csv = app(AttendanceMonthlyReport::class)->csv($this->report['rows']);

        return response()->streamDownload(
            function () use ($csv): void {
                echo $csv;
            },
            sprintf('attendance-summary-%04d-%02d.csv', $this->selectedYear(), $this->selectedMonth()),
            ['Content-Type' => 'text/csv'],
        );
    }

    /**
     * @return array<int, string>
     */
    protected function monthOptions(): array
    {
        return collect(range(1, 12))
            ->mapWithKeys(fn (int $month): array => [
                $month => Carbon::create(null, $month, 1)->locale(app()->getLocale())->translatedFormat('F'),
            ])
            ->all();
    }

    /**
     * The browser can send any value; anything outside the offered options falls back to this month.
     */
    protected function selectedYear(): int
    {
        $year = (int) ($this->data['year'] ?? 0);

        return $year >= now()->year - 5 && $year <= now()->year + 1 ? $year : now()->year;
    }

    protected function selectedMonth(): int
    {
        $month = (int) ($this->data['month'] ?? 0);

        return $month >= 1 && $month <= 12 ? $month : now()->month;
    }
}
