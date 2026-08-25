<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Enums\Operations\BackupFrequency;
use App\Enums\Operations\BackupType;
use App\Settings\OperationsSettings;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Webkul\Support\Filament\Clusters\Settings;

class ManageOperations extends SettingsPage
{
    use HasPageShield;

    protected static ?string $cluster = Settings::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string $settings = OperationsSettings::class;

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): string
    {
        return __('operations.settings.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('operations.settings.navigation');
    }

    public function getTitle(): string
    {
        return __('operations.settings.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('operations.settings.sections.monitoring'))
                ->schema([
                    Toggle::make('monitoring_enabled')
                        ->label(__('operations.settings.monitoring_enabled')),
                    Toggle::make('alerts_enabled')
                        ->label(__('operations.settings.alerts_enabled')),
                    TextInput::make('disk_warning_threshold')
                        ->label(__('operations.settings.disk_warning_threshold'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(99)
                        ->suffix('%'),
                    TextInput::make('disk_critical_threshold')
                        ->label(__('operations.settings.disk_critical_threshold'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(100)
                        ->suffix('%'),
                    TextInput::make('failed_jobs_threshold')
                        ->label(__('operations.settings.failed_jobs_threshold'))
                        ->numeric()
                        ->minValue(1),
                    TextInput::make('scheduler_warning_minutes')
                        ->label(__('operations.settings.scheduler_warning_minutes'))
                        ->numeric()
                        ->minValue(1),
                    TextInput::make('scheduler_critical_minutes')
                        ->label(__('operations.settings.scheduler_critical_minutes'))
                        ->numeric()
                        ->minValue(1),
                    TextInput::make('health_history_retention_days')
                        ->label(__('operations.settings.health_history_retention_days'))
                        ->numeric()
                        ->minValue(1),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make(__('operations.settings.sections.backup'))
                ->schema([
                    Toggle::make('backup_enabled')
                        ->label(__('operations.settings.backup_enabled')),
                    Select::make('backup_frequency')
                        ->label(__('operations.settings.backup_frequency'))
                        ->options(BackupFrequency::class),
                    TextInput::make('backup_time')
                        ->label(__('operations.settings.backup_time'))
                        ->placeholder('02:00'),
                    Select::make('backup_type')
                        ->label(__('operations.settings.backup_type'))
                        ->options(BackupType::class),
                    TextInput::make('backup_disk')
                        ->label(__('operations.settings.backup_disk'))
                        ->default('private'),
                    TextInput::make('backup_retention_count')
                        ->label(__('operations.settings.backup_retention_count'))
                        ->numeric()
                        ->minValue(1),
                    Toggle::make('backup_alert_on_failure')
                        ->label(__('operations.settings.backup_alert_on_failure')),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
