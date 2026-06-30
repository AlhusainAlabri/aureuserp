<?php

namespace App\Filament\Pages;

use App\Support\ModuleLauncher\ModuleLauncherItems;
use App\Support\ModuleLauncher\ModuleLauncherPreferenceStore;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Section;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class ModuleLauncher extends Page
{
    protected static string $routePath = '/';

    protected static ?string $slug = 'launcher';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = -20;

    protected string $view = 'filament.pages.module-launcher';

    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

    public static function getNavigationLabel(): string
    {
        return __('module-launcher.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.dashboard');
    }

    public function getTitle(): string|Htmlable
    {
        return __('module-launcher.greeting', ['name' => auth()->user()?->name ?? '']);
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    /**
     * @return Collection<int, array{key: string, label: string, icon: string, url: string, color: string, type: string, opensInNewTab: bool}>
     */
    public function getAllModuleItems(): Collection
    {
        return ModuleLauncherItems::allForCurrentUser();
    }

    /**
     * @return Collection<int, array{key: string, label: string, icon: string, url: string, color: string, type: string, opensInNewTab: bool}>
     */
    public function getVisibleModuleItems(): Collection
    {
        return ModuleLauncherItems::visibleForCurrentUser();
    }

    public function getHiddenItemsCount(): int
    {
        return ModuleLauncherPreferenceStore::hiddenCount();
    }

    /**
     * @return array<string>
     */
    public function getPageClasses(): array
    {
        return ['fi-page-module-launcher'];
    }

    protected function getHeaderActions(): array
    {
        if ($this->getAllModuleItems()->isEmpty()) {
            return [];
        }

        return [
            $this->makeCustomizeLauncherAction(),
        ];
    }

    public function makeCustomizeLauncherAction(): Action
    {
        return Action::make('customizeLauncher')
            ->label(__('module-launcher.customize.action'))
            ->icon('heroicon-o-adjustments-horizontal')
            ->color('gray')
            ->badge(fn (): ?string => $this->getHiddenItemsCount() > 0
                ? (string) $this->getHiddenItemsCount()
                : null)
            ->slideOver()
            ->modalHeading(__('module-launcher.customize.heading'))
            ->modalDescription(__('module-launcher.customize.description'))
            ->modalSubmitActionLabel(__('module-launcher.customize.save'))
            ->fillForm(function (): array {
                $visible = ModuleLauncherPreferenceStore::visibleKeys(
                    $this->getAllModuleItems()->pluck('key')->all(),
                );

                return [
                    'visible_module_items' => array_values(array_filter(
                        $visible,
                        fn (string $key): bool => str_starts_with($key, 'module:'),
                    )),
                    'visible_shortcut_items' => array_values(array_filter(
                        $visible,
                        fn (string $key): bool => str_starts_with($key, 'shortcut:'),
                    )),
                ];
            })
            ->schema([
                Section::make(__('module-launcher.customize.groups.modules'))
                    ->description(__('module-launcher.customize.section_help'))
                    ->schema([
                        CheckboxList::make('visible_module_items')
                            ->hiddenLabel()
                            ->options(fn (): array => ModuleLauncherItems::customizeModuleOptions())
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(2)
                            ->gridDirection('row')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (): bool => ModuleLauncherItems::customizeModuleOptions() !== []),
                Section::make(__('module-launcher.customize.groups.shortcuts'))
                    ->schema([
                        CheckboxList::make('visible_shortcut_items')
                            ->hiddenLabel()
                            ->options(fn (): array => ModuleLauncherItems::customizeShortcutOptions())
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(2)
                            ->gridDirection('row')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (): bool => ModuleLauncherItems::customizeShortcutOptions() !== []),
            ])
            ->action(function (array $data): void {
                $allKeys = $this->getAllModuleItems()->pluck('key')->all();
                $visible = array_merge(
                    $data['visible_module_items'] ?? [],
                    $data['visible_shortcut_items'] ?? [],
                );

                ModuleLauncherPreferenceStore::syncVisibleItems($allKeys, $visible);

                Notification::make()
                    ->title(__('module-launcher.customize.saved'))
                    ->success()
                    ->send();
            })
            ->extraModalFooterActions([
                Action::make('resetLauncherVisibility')
                    ->label(__('module-launcher.customize.reset'))
                    ->color('gray')
                    ->action(function (): void {
                        ModuleLauncherPreferenceStore::reset();

                        Notification::make()
                            ->title(__('module-launcher.customize.reset_done'))
                            ->success()
                            ->send();

                        $this->unmountAction();
                    }),
            ]);
    }
}
