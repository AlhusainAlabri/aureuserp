<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DashboardShortcutResource\Pages\CreateDashboardShortcut;
use App\Filament\Resources\DashboardShortcutResource\Pages\EditDashboardShortcut;
use App\Filament\Resources\DashboardShortcutResource\Pages\ListDashboardShortcuts;
use App\Models\DashboardShortcut;
use App\Support\ModuleLauncher\ModuleLauncherItems;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Support\HtmlString;
use Illuminate\View\ComponentAttributeBag;

class DashboardShortcutResource extends Resource
{
    protected static ?string $model = DashboardShortcut::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?int $navigationSort = 50;

    protected static ?string $slug = 'settings/dashboard-shortcuts';

    public static function getNavigationLabel(): string
    {
        return __('module-launcher.shortcuts.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.setting');
    }

    public static function getModelLabel(): string
    {
        return __('module-launcher.shortcuts.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('module-launcher.shortcuts.plural');
    }

    public static function canAccess(): bool
    {
        if (! DbSchema::hasTable('dashboard_shortcuts')) {
            return false;
        }

        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        $roles = collect($user->getRoleNames())->map(fn (string $name): string => strtolower($name));

        return $roles->intersect(['super_admin', 'admin', 'admin_manager', 'general_manager'])->isNotEmpty();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('module-launcher.shortcuts.form.section'))
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('title_en')
                            ->label(__('module-launcher.shortcuts.form.title_en'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        TextInput::make('title_ar')
                            ->label(__('module-launcher.shortcuts.form.title_ar'))
                            ->maxLength(255)
                            ->columnSpan(1),
                        TextInput::make('url')
                            ->label(__('module-launcher.shortcuts.form.url'))
                            ->required()
                            ->maxLength(2048)
                            ->placeholder('/admin/projects/task-hub')
                            ->helperText(__('module-launcher.shortcuts.form.url_help'))
                            ->columnSpanFull(),
                        Select::make('icon')
                            ->label(__('module-launcher.shortcuts.form.icon'))
                            ->options(ModuleLauncherItems::shortcutIconOptions())
                            ->default('heroicon-o-link')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->native(false)
                            ->helperText(__('module-launcher.shortcuts.form.icon_help'))
                            ->columnSpanFull(),
                        Placeholder::make('icon_preview')
                            ->label(__('module-launcher.shortcuts.form.icon_preview'))
                            ->content(function (Get $get): HtmlString {
                                $icon = $get('icon');

                                if (blank($icon)) {
                                    return new HtmlString(
                                        '<p class="text-sm text-gray-500 dark:text-gray-400">'
                                        .e(__('module-launcher.shortcuts.form.icon_preview_empty'))
                                        .'</p>'
                                    );
                                }

                                $label = ModuleLauncherItems::shortcutIconOptionsFlat()[$icon] ?? $icon;
                                $svg = \Filament\Support\generate_icon_html(
                                    $icon,
                                    attributes: new ComponentAttributeBag(['class' => 'h-8 w-8']),
                                );

                                return new HtmlString(
                                    '<div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/50">'
                                    .'<span class="flex h-12 w-12 items-center justify-center rounded-full bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">'
                                    .$svg
                                    .'</span>'
                                    .'<div class="min-w-0 text-start"><p class="text-sm font-medium text-gray-950 dark:text-white">'
                                    .e($label)
                                    .'</p><p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">'
                                    .e($icon)
                                    .'</p></div></div>'
                                );
                            })
                            ->columnSpanFull(),
                        Select::make('color')
                            ->label(__('module-launcher.shortcuts.form.color'))
                            ->options(ModuleLauncherItems::availableColors())
                            ->default('info')
                            ->required()
                            ->native(false)
                            ->columnSpan(1),
                        TextInput::make('sort')
                            ->label(__('module-launcher.shortcuts.form.sort'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->columnSpan(1),
                        Toggle::make('is_active')
                            ->label(__('module-launcher.shortcuts.form.is_active'))
                            ->default(true)
                            ->columnSpan(1),
                        Toggle::make('opens_in_new_tab')
                            ->label(__('module-launcher.shortcuts.form.opens_in_new_tab'))
                            ->default(false)
                            ->columnSpanFull(),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title_en')
                    ->label(__('module-launcher.shortcuts.table.title_en'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title_ar')
                    ->label(__('module-launcher.shortcuts.table.title_ar'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('url')
                    ->label(__('module-launcher.shortcuts.table.url'))
                    ->limit(40)
                    ->tooltip(fn (DashboardShortcut $record): string => $record->url),
                IconColumn::make('icon')
                    ->label(__('module-launcher.shortcuts.table.icon'))
                    ->icon(fn (DashboardShortcut $record): string => $record->icon),
                TextColumn::make('sort')
                    ->label(__('module-launcher.shortcuts.table.sort'))
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label(__('module-launcher.shortcuts.table.is_active')),
            ])
            ->defaultSort('sort')
            ->reorderable('sort')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('module-launcher.shortcuts.table.is_active')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListDashboardShortcuts::route('/'),
            'create' => CreateDashboardShortcut::route('/create'),
            'edit'   => EditDashboardShortcut::route('/{record}/edit'),
        ];
    }
}
