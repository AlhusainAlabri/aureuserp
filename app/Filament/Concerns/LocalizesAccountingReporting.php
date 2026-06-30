<?php

namespace App\Filament\Concerns;

use App\Filament\Extensions\AccountingResourceExtensions;
use Carbon\Carbon;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Webkul\Account\Models\Journal;

trait LocalizesAccountingReporting
{
    protected function localizedDateRangeField(string $label, ?Closure $afterStateUpdated = null): DateRangePicker
    {
        $field = DateRangePicker::make('date_range')
            ->label($label)
            ->suffixIcon('heroicon-o-calendar')
            ->defaultThisMonth()
            ->ranges(AccountingResourceExtensions::localizedDateRanges())
            ->alwaysShowCalendar()
            ->live();

        if ($afterStateUpdated instanceof Closure) {
            $field->afterStateUpdated($afterStateUpdated);
        } else {
            $field->afterStateUpdated(fn () => null);
        }

        return $field;
    }

    /**
     * @return array<int, Component>
     */
    protected function localizedReportingFilterSchema(string $dateRangeLabel, string $journalsLabel): array
    {
        return [
            Section::make()
                ->columns([
                    'default' => 1,
                    'sm'      => 2,
                ])
                ->schema([
                    DateRangePicker::make('date_range')
                        ->label($dateRangeLabel)
                        ->suffixIcon('heroicon-o-calendar')
                        ->defaultThisMonth()
                        ->ranges(AccountingResourceExtensions::localizedDateRanges())
                        ->alwaysShowCalendar()
                        ->live()
                        ->afterStateUpdated(fn () => null),
                    Select::make('journals')
                        ->label($journalsLabel)
                        ->multiple()
                        ->options(fn () => Journal::pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn () => null),
                ])
                ->columnSpanFull(),
        ];
    }

    protected function localizedReportHeading(string $translationKey, Carbon|string $date): string
    {
        $parsedDate = $date instanceof Carbon ? $date : Carbon::parse($date);

        return __($translationKey, [
            'date' => $parsedDate->locale(app()->getLocale())->translatedFormat('j F Y'),
        ]);
    }
}
