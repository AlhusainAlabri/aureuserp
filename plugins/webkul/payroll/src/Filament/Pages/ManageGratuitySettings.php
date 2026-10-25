<?php

namespace Webkul\Payroll\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Webkul\Payroll\Enums\PasiCategory;
use Webkul\Payroll\Filament\Clusters\Configuration;
use Webkul\Payroll\Settings\GratuitySettings;

class ManageGratuitySettings extends SettingsPage
{
    use HasPageShield;

    protected static string $settings = GratuitySettings::class;

    protected static ?string $cluster = Configuration::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('payroll::payroll.gratuity.settings.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('payroll::payroll.navigation.group');
    }

    public function getTitle(): string
    {
        return __('payroll::payroll.gratuity.settings.title');
    }

    public function getBreadcrumbs(): array
    {
        return [__('payroll::payroll.gratuity.settings.title')];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('payroll::payroll.gratuity.settings.title'))
                ->schema([
                    TextInput::make('minimum_service_years')
                        ->label(__('payroll::payroll.fields.minimum_service_years'))
                        ->helperText(__('payroll::payroll.gratuity.settings.minimum_service_hint'))
                        ->numeric()
                        ->required()
                        ->minValue(0),
                    Select::make('eligible_category')
                        ->label(__('payroll::payroll.fields.eligible_category'))
                        ->helperText(__('payroll::payroll.gratuity.settings.eligible_category_hint'))
                        ->options(PasiCategory::options())
                        ->required()
                        ->native(false),
                    TextInput::make('leave_payout_divisor')
                        ->label(__('payroll::payroll.fields.leave_payout_divisor'))
                        ->helperText(__('payroll::payroll.gratuity.settings.divisor_hint'))
                        ->numeric()
                        ->integer()
                        ->required()
                        ->minValue(1),
                ]),
        ]);
    }
}
