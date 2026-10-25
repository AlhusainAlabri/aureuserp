<?php

namespace Webkul\Payroll\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Webkul\Payroll\Filament\Clusters\Configuration;
use Webkul\Payroll\Settings\PayrollAttendanceSettings;

class ManagePayrollAttendanceSettings extends SettingsPage
{
    use HasPageShield;

    protected static string $settings = PayrollAttendanceSettings::class;

    protected static ?string $cluster = Configuration::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-finger-print';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('payroll::payroll.attendance.settings.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('payroll::payroll.navigation.group');
    }

    public function getTitle(): string
    {
        return __('payroll::payroll.attendance.settings.title');
    }

    public function getBreadcrumbs(): array
    {
        return [__('payroll::payroll.attendance.settings.title')];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('payroll::payroll.attendance.settings.title'))
                ->description(__('payroll::payroll.attendance.settings.description'))
                ->schema([
                    Toggle::make('deduct_absence')
                        ->label(__('payroll::payroll.fields.deduct_absence'))
                        ->helperText(__('payroll::payroll.attendance.settings.deduct_absence_hint')),
                    Toggle::make('deduct_lateness')
                        ->label(__('payroll::payroll.fields.deduct_lateness'))
                        ->helperText(__('payroll::payroll.attendance.settings.deduct_lateness_hint')),
                    Toggle::make('pay_overtime')
                        ->label(__('payroll::payroll.fields.pay_overtime'))
                        ->helperText(__('payroll::payroll.attendance.settings.pay_overtime_hint')),
                ]),
        ]);
    }
}
