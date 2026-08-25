<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Enums\NavigationAppearance;
use App\Enums\NavigationMenuType;
use App\Enums\SidebarDefaultState;
use App\Settings\NavigationSettings;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Select;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Webkul\Support\Filament\Clusters\Settings;

class ManageNavigation extends SettingsPage
{
    use HasPageShield;

    protected static ?string $cluster = Settings::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bars-3-bottom-left';

    protected static string $settings = NavigationSettings::class;

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): string
    {
        return __('navigation-settings.navigation.group');
    }

    public function getBreadcrumbs(): array
    {
        return [
            __('navigation-settings.navigation.breadcrumb'),
        ];
    }

    public function getTitle(): string
    {
        return __('navigation-settings.navigation.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation-settings.navigation.label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('navigation-settings.sections.menu'))
                    ->schema([
                        Select::make('menu_type')
                            ->label(__('navigation-settings.form.menu_type.label'))
                            ->helperText(__('navigation-settings.form.menu_type.helper'))
                            ->options(NavigationMenuType::class)
                            ->required()
                            ->live(),
                    ])
                    ->columnSpanFull(),
                Section::make(__('navigation-settings.sections.sidebar'))
                    ->schema([
                        Select::make('sidebar_default_state')
                            ->label(__('navigation-settings.form.sidebar_default_state.label'))
                            ->helperText(__('navigation-settings.form.sidebar_default_state.helper'))
                            ->options(SidebarDefaultState::class)
                            ->required()
                            ->visible(fn (Get $get): bool => self::isSidebarMenuType($get('menu_type'))),
                    ])
                    ->columnSpanFull()
                    ->visible(fn (Get $get): bool => self::isSidebarMenuType($get('menu_type'))),
                Section::make(__('navigation-settings.sections.appearance'))
                    ->schema([
                        Select::make('menu_appearance')
                            ->label(__('navigation-settings.form.menu_appearance.label'))
                            ->helperText(__('navigation-settings.form.menu_appearance.helper'))
                            ->options(NavigationAppearance::class)
                            ->required()
                            ->visible(fn (Get $get): bool => self::isSidebarMenuType($get('menu_type'))),
                    ])
                    ->columnSpanFull()
                    ->visible(fn (Get $get): bool => self::isSidebarMenuType($get('menu_type'))),
            ]);
    }

    protected static function isSidebarMenuType(NavigationMenuType|string|null $menuType): bool
    {
        if ($menuType instanceof NavigationMenuType) {
            return $menuType === NavigationMenuType::Sidebar;
        }

        return $menuType === NavigationMenuType::Sidebar->value;
    }
}
