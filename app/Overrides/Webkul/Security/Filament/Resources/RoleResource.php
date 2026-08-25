<?php

namespace Webkul\Security\Filament\Resources;

use App\Enums\ModuleAccessLevel;
use App\Livewire\Security\RolePermissionsEditor;
use App\Services\Security\ModulePermissionResolver;
use App\Services\Security\RolePermissionResolver;
use App\Services\Security\RoleTemplateService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource as ShieldRoleResource;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Livewire as LivewireComponent;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Webkul\Security\Filament\Resources\RoleResource\Pages\CreateRole;
use Webkul\Security\Filament\Resources\RoleResource\Pages\EditRole;
use Webkul\Security\Filament\Resources\RoleResource\Pages\ListRoles;
use Webkul\Security\Filament\Resources\RoleResource\Pages\ViewRole;
use Webkul\Security\Models\Role;

class RoleResource extends ShieldRoleResource
{
    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    protected static bool $isGloballySearchable = false;

    protected static $permissionsCollection;

    public static $permissions = null;

    protected static ?Collection $allFormPermissions = null;

    public static function canGloballySearch(): bool
    {
        return false;
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return null;
    }

    public static function getActiveNavigationIcon(): BackedEnum|Htmlable|null|string
    {
        return null;
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make(__('role-management.wizard.details'))
                        ->icon('heroicon-o-identification')
                        ->schema(static::getDetailsStepSchema()),
                    Step::make(__('role-management.wizard.access'))
                        ->icon('heroicon-o-squares-2x2')
                        ->schema(static::getAccessStepSchema()),
                    Step::make(__('role-management.wizard.review'))
                        ->icon('heroicon-o-check-circle')
                        ->schema(static::getReviewStepSchema()),
                ])
                    ->visible(fn (string $operation): bool => $operation === 'create')
                    ->columnSpanFull()
                    ->skippable(false),
                Grid::make()
                    ->schema(static::getEditFormSchema())
                    ->visible(fn (string $operation): bool => $operation === 'edit')
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('role-management.sections.basics'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('filament-shield::filament-shield.field.name')),
                        TextEntry::make('guard_name')
                            ->label(__('filament-shield::filament-shield.field.guard_name')),
                        TextEntry::make('permissions_count')
                            ->label(__('role-management.view.permissions_count'))
                            ->state(fn (Role $record): int => $record->permissions()->count()),
                        TextEntry::make('updated_at')
                            ->label(__('filament-shield::filament-shield.column.updated_at'))
                            ->dateTime(),
                    ])
                    ->columns(2),
                Section::make(__('role-management.view.module_summary'))
                    ->schema([
                        RepeatableEntry::make('module_summary')
                            ->label('')
                            ->state(function (Role $record): array {
                                $resolver = app(ModulePermissionResolver::class);
                                $moduleAccess = $resolver->inferModuleAccess($record->permissions()->pluck('name'));
                                $labels = $resolver->getModuleLabels();

                                return collect($moduleAccess)
                                    ->filter(fn (string $level): bool => $level !== ModuleAccessLevel::None->value)
                                    ->map(fn (string $level, string $module): array => [
                                        'module' => $labels[$module] ?? $module,
                                        'level'  => ModuleAccessLevel::tryFrom($level)?->getLabel() ?? $level,
                                        'count'  => $resolver->countSelectedInModule($module, $record->permissions()->pluck('name')),
                                        'total'  => $resolver->getModulePermissionCount($module),
                                    ])
                                    ->values()
                                    ->all();
                            })
                            ->schema([
                                TextEntry::make('module'),
                                TextEntry::make('level')->badge(),
                                TextEntry::make('count')
                                    ->label(__('role-management.advanced.progress'))
                                    ->formatStateUsing(fn (int $state, array $record): string => __('role-management.advanced.progress', [
                                        'selected' => $state,
                                        'total'    => $record['total'],
                                    ])),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }

    /**
     * @return array<int, Component>
     */
    public static function getDetailsStepSchema(): array
    {
        return [
            Section::make(__('role-management.sections.basics'))
                ->schema(static::getBasicsFields())
                ->columns(2)
                ->columnSpanFull(),
            Section::make(__('role-management.sections.starting'))
                ->schema(static::getStartingPointFields())
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function getAccessStepSchema(): array
    {
        return [
            ...static::getModuleAccessComponents(),
            static::getAdvancedSection(),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function getReviewStepSchema(): array
    {
        return [
            Html::make(fn (Get $get): HtmlString => static::buildReviewSummaryHtml([
                'name'                  => $get('name'),
                'guard_name'            => $get('guard_name'),
                'starting_point'        => $get('starting_point'),
                'role_template'         => $get('role_template'),
                'clone_role_id'         => $get('clone_role_id'),
                'module_access'         => $get('module_access') ?? [],
                'advanced_permissions'  => $get('advanced_permissions') ?? [],
                'advanced_editor_open'  => (bool) $get('advanced_editor_open'),
            ]))
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function getEditFormSchema(): array
    {
        return [
            Section::make(__('role-management.sections.basics'))
                ->schema(static::getBasicsFields())
                ->columns(2)
                ->columnSpanFull(),
            Section::make(__('role-management.sections.modules'))
                ->schema(static::getModuleAccessComponents())
                ->columnSpanFull(),
            static::getAdvancedSection(),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function getBasicsFields(): array
    {
        return [
            TextInput::make('name')
                ->label(__('filament-shield::filament-shield.field.name'))
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule): Unique => Utils::isTenancyEnabled() ? $rule->where(Utils::getTenantModelForeignKey(), Filament::getTenant()?->id) : $rule
                )
                ->required()
                ->maxLength(255)
                ->disabled(fn (?Model $record): bool => $record instanceof Role && $record->isSystemRole())
                ->dehydrated(),
            Select::make('guard_name')
                ->label(__('filament-shield::filament-shield.field.guard_name'))
                ->native(false)
                ->selectablePlaceholder(false)
                ->options([
                    'web'     => __('security::filament/resources/role.form.fields.web'),
                    'sanctum' => __('security::filament/resources/role.form.fields.sanctum'),
                ])
                ->default(Utils::getFilamentAuthGuard())
                ->disabled(fn (?Model $record): bool => $record instanceof Role && $record->isSystemRole())
                ->dehydrated(),
            Select::make(config('permission.column_names.team_foreign_key'))
                ->label(__('filament-shield::filament-shield.field.team'))
                ->placeholder(__('filament-shield::filament-shield.field.team.placeholder'))
                ->default(Filament::getTenant()?->id)
                ->options(fn (): Arrayable => Utils::getTenantModel() ? Utils::getTenantModel()::pluck('name', 'id') : collect())
                ->hidden(fn (): bool => ! (static::shield()->isCentralApp() && Utils::isTenancyEnabled()))
                ->dehydrated(fn (): bool => ! (static::shield()->isCentralApp() && Utils::isTenancyEnabled())),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function getStartingPointFields(): array
    {
        return [
            Select::make('starting_point')
                ->label(__('role-management.starting_point.label'))
                ->options([
                    'blank'    => __('role-management.starting_point.blank'),
                    'template' => __('role-management.starting_point.template'),
                    'clone'    => __('role-management.starting_point.clone'),
                ])
                ->default('blank')
                ->native(false)
                ->live()
                ->required(),
            Select::make('role_template')
                ->label(__('role-management.fields.role_template'))
                ->options(fn (): array => app(RoleTemplateService::class)->getTemplateSelectOptions())
                ->native(false)
                ->searchable()
                ->visible(fn (Get $get): bool => $get('starting_point') === 'template')
                ->required(fn (Get $get): bool => $get('starting_point') === 'template')
                ->live()
                ->afterStateUpdated(function (Set $set, ?string $state): void {
                    $templateService = app(RoleTemplateService::class);
                    $set('module_access', $templateService->getModuleAccessForTemplate($state));
                    $set('advanced_permissions', $templateService->resolvePermissions($state)->all());
                }),
            Select::make('clone_role_id')
                ->label(__('role-management.fields.clone_role_id'))
                ->options(fn (): array => Role::query()->orderBy('name')->pluck('name', 'id')->all())
                ->native(false)
                ->searchable()
                ->visible(fn (Get $get): bool => $get('starting_point') === 'clone')
                ->required(fn (Get $get): bool => $get('starting_point') === 'clone')
                ->live()
                ->afterStateUpdated(function (Set $set, mixed $state): void {
                    $role = Role::query()->find($state);

                    if ($role === null) {
                        return;
                    }

                    $permissions = $role->permissions()->pluck('name');
                    $resolver = app(ModulePermissionResolver::class);

                    $set('module_access', $resolver->inferModuleAccess($permissions));
                    $set('advanced_permissions', $permissions->all());
                }),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function getModuleAccessComponents(): array
    {
        return [
            Toggle::make('grant_all_modules')
                ->label(__('role-management.fields.grant_all_modules'))
                ->dehydrated(false)
                ->live()
                ->afterStateUpdated(function (Set $set, bool $state): void {
                    $resolver = app(ModulePermissionResolver::class);
                    $access = collect($resolver->getModuleNames())
                        ->mapWithKeys(fn (string $module): array => [
                            $module => $state ? ModuleAccessLevel::Full->value : ModuleAccessLevel::None->value,
                        ])
                        ->all();

                    $set('module_access', $access);
                }),
            Hidden::make('module_access')
                ->default([]),
            Grid::make([
                'default' => 1,
                'md'      => 2,
                'xl'      => 3,
            ])
                ->schema(function (): array {
                    $resolver = app(ModulePermissionResolver::class);
                    $labels = $resolver->getModuleLabels();

                    return collect($resolver->getModuleNames())
                        ->map(function (string $module) use ($labels, $resolver): Section {
                            $total = $resolver->getModulePermissionCount($module);

                            return Section::make($labels[$module] ?? $module)
                                ->description(__('role-management.module_access.selected_count', [
                                    'selected' => 0,
                                    'total'    => $total,
                                ]))
                                ->compact()
                                ->schema([
                                    Select::make('module_access.'.$module)
                                        ->label(__('role-management.fields.module_access'))
                                        ->options(ModuleAccessLevel::options())
                                        ->default(ModuleAccessLevel::None->value)
                                        ->native(false)
                                        ->live(),
                                ]);
                        })
                        ->all();
                })
                ->columnSpanFull(),
        ];
    }

    public static function getAdvancedSection(): Section
    {
        return Section::make(__('role-management.sections.advanced'))
            ->icon('heroicon-o-adjustments-horizontal')
            ->collapsed()
            ->schema([
                Toggle::make('advanced_editor_open')
                    ->label(__('role-management.fields.advanced_editor_open'))
                    ->default(false)
                    ->live()
                    ->dehydrated(),
                Hidden::make('advanced_permissions')
                    ->default([]),
                LivewireComponent::make(RolePermissionsEditor::class)
                    ->lazy()
                    ->visible(fn (Get $get): bool => (bool) $get('advanced_editor_open'))
                    ->data(fn (Get $get): array => [
                        'advancedPermissions' => $get('advanced_permissions') ?? [],
                    ])
                    ->columnSpanFull(),
            ])
            ->columnSpanFull();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->badge()
                    ->label(__('filament-shield::filament-shield.column.name'))
                    ->formatStateUsing(fn ($state): string => Str::headline($state))
                    ->colors(['primary'])
                    ->searchable(),
                TextColumn::make('guard_name')
                    ->badge()
                    ->label(__('filament-shield::filament-shield.column.guard_name')),
                TextColumn::make('permissions_count')
                    ->badge()
                    ->label(__('filament-shield::filament-shield.column.permissions'))
                    ->counts('permissions')
                    ->colors(['success']),
                TextColumn::make('updated_at')
                    ->label(__('filament-shield::filament-shield.column.updated_at'))
                    ->dateTime(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, Model $record): void {
                        if (static::isProtectedRoleRecord($record)) {
                            Notification::make()
                                ->danger()
                                ->title(__('security::filament/resources/role.notification.system-role-delete.title'))
                                ->body(__('security::filament/resources/role.notification.system-role-delete.body'))
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->fetchSelectedRecords(true)
                    ->authorizeIndividualRecords('delete')
                    ->action(function (DeleteBulkAction $action, Collection $records): void {
                        $deletableRecords = $records->reject(
                            fn (Model $record): bool => static::isProtectedRoleRecord($record)
                        );

                        if ($deletableRecords->isEmpty()) {
                            $action->cancel();

                            return;
                        }

                        $deletableRecords->each(fn (Model $record): ?bool => $record->delete());
                    }),
            ])
            ->defaultSort('created_at', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'view'   => ViewRole::route('/{record}'),
            'edit'   => EditRole::route('/{record}/edit'),
        ];
    }

    public static function getTabFormComponentForResources(): Component
    {
        return self::shield()->hasSimpleResourcePermissionView()
            ? self::getTabFormComponentForSimpleResourcePermissionsView()
            : Tab::make('resources')
                ->label(__('filament-shield::filament-shield.resources'))
                ->visible(fn (): bool => Utils::isResourceTabEnabled())
                ->badge(static::getResourceTabBadgeCount())
                ->schema(static::getPluginResourceEntitiesSchema());
    }

    public static function getTabFormComponentForPage(): Component
    {
        $options = static::getPageOptions();
        $count = count($options);

        return Tab::make('pages')
            ->label(__('filament-shield::filament-shield.pages'))
            ->visible(fn (): bool => Utils::isPageTabEnabled() && $count > 0)
            ->badge($count)
            ->schema(static::getPluginPageEntitiesSchema());
    }

    public static function getTabFormComponentForWidget(): Component
    {
        $options = static::getWidgetOptions();
        $count = count($options);

        return Tab::make('widgets')
            ->label(__('filament-shield::filament-shield.widgets'))
            ->visible(fn (): bool => Utils::isWidgetTabEnabled() && $count > 0)
            ->badge($count)
            ->schema(static::getPluginWidgetEntitiesSchema());
    }

    public static function getAllFormPermissions(): Collection
    {
        if (static::$allFormPermissions instanceof Collection) {
            return static::$allFormPermissions;
        }

        $resourcePermissions = collect(static::getResources())
            ->flatMap(fn (array $entity): array => array_keys(static::getResourcePermissionOptions($entity)));

        $customPermissions = app(ModulePermissionResolver::class)->getCustomPermissions();

        return static::$allFormPermissions = $resourcePermissions
            ->merge(array_keys(static::getPageOptions()))
            ->merge(array_keys(static::getWidgetOptions()))
            ->merge($customPermissions)
            ->unique()
            ->values();
    }

    public static function getPluginResources(): ?array
    {
        return once(fn (): array => collect(static::getResources())
            ->groupBy(function ($value, $key) {
                return explode('\\', $key)[1] ?? 'Unknown';
            })
            ->toArray());
    }

    public static function getResources(): ?array
    {
        return FilamentShield::discoverResources()
            ->reject(function ($resource) {
                if ($resource == 'BezhanSalleh\FilamentShield\Resources\Roles\RoleResource') {
                    return true;
                }

                if (Utils::getConfig()->resources->exclude) {
                    return in_array(
                        Str::of($resource)->afterLast('\\'),
                        Utils::getConfig()->resources->exclude
                    );
                }
            })
            ->mapWithKeys(function (string $resource) {
                return [
                    $resource => [
                        'model'        => str($resource::getModel())->afterLast('\\')->toString(),
                        'modelFqcn'    => str($resource::getModel())->toString(),
                        'resourceFqcn' => $resource,
                    ],
                ];
            })
            ->sortKeys()
            ->toArray();
    }

    public static function getPluginPages(): array
    {
        return collect(FilamentShield::getPages())
            ->groupBy(function ($value, $key) {
                return explode('\\', $key)[1] ?? 'Unknown';
            })
            ->toArray();
    }

    public static function getPluginWidgets(): array
    {
        return collect(FilamentShield::getWidgets())
            ->groupBy(function ($value, $key) {
                return explode('\\', $key)[1] ?? 'Unknown';
            })
            ->toArray();
    }

    public static function getPluginResourceEntitiesSchema(): ?array
    {
        return collect(static::getPluginResources())
            ->sortKeys()
            ->map(function ($plugin, $key) {
                $hasAnyOptions = collect($plugin)->contains(function ($entity) {
                    return ! empty(static::getResourcePermissionOptions($entity));
                });

                if (! $hasAnyOptions) {
                    return;
                }

                return Section::make($key)
                    ->collapsible()
                    ->collapsed()
                    ->persistCollapsed()
                    ->schema([
                        Grid::make()
                            ->schema(function () use ($plugin) {
                                return collect($plugin)
                                    ->flatMap(function ($entity) {
                                        $options = static::getResourcePermissionOptions($entity);

                                        if (empty($options)) {
                                            return [];
                                        }

                                        $fieldsetLabel = strval(
                                            static::shield()->hasLocalizedPermissionLabels()
                                                ? FilamentShield::getLocalizedResourceLabel($entity['resourceFqcn'])
                                                : $entity['model']
                                        );

                                        return [
                                            Fieldset::make($fieldsetLabel)
                                                ->schema([
                                                    static::getCheckBoxListComponentForResource($entity)->hiddenLabel(),
                                                ])
                                                ->columnSpan(static::shield()->getSectionColumnSpan()),
                                        ];
                                    })
                                    ->toArray();
                            })
                            ->columns(static::shield()->getGridColumns()),
                    ]);
            })
            ->toArray();
    }

    public static function getPluginPageEntitiesSchema(): ?array
    {
        return collect(static::getPluginPages())
            ->sortKeys()
            ->map(function ($plugin, $key) {
                return Section::make($key)
                    ->collapsible()
                    ->collapsed()
                    ->persistCollapsed()
                    ->schema([
                        Grid::make()
                            ->schema(function () use ($plugin, $key) {
                                $options = collect($plugin)
                                    ->flatMap(fn ($page) => $page['permissions'])
                                    ->toArray();

                                return [
                                    static::getCheckboxListFormComponent(
                                        name: $key.'_pages_tab',
                                        options: $options,
                                    ),
                                ];
                            }),
                    ]);
            })
            ->values()
            ->toArray();
    }

    public static function getPluginWidgetEntitiesSchema(): ?array
    {
        return collect(static::getPluginWidgets())
            ->sortKeys()
            ->map(function ($plugin, $key) {
                return Section::make($key)
                    ->collapsible()
                    ->collapsed()
                    ->persistCollapsed()
                    ->schema([
                        Grid::make()
                            ->schema(function () use ($plugin, $key) {
                                $options = collect($plugin)
                                    ->flatMap(fn ($page) => $page['permissions'])
                                    ->toArray();

                                return [
                                    static::getCheckboxListFormComponent(
                                        name: $key.'_widgets_tab',
                                        options: $options,
                                    ),
                                ];
                            }),
                    ]);
            })
            ->values()
            ->toArray();
    }

    public static function getCheckboxListFormComponent(
        string $name,
        array $options,
        bool $searchable = true,
        array|int|string|null $columns = null,
        array|int|string|null $columnSpan = null
    ): Component {
        return CheckboxList::make($name)
            ->hiddenLabel()
            ->options(fn (): array => $options)
            ->searchable($searchable)
            ->afterStateHydrated(function (Component $component, string $operation, ?Model $record) use ($options): void {
                static::setPermissionStateForRecordPermissions(
                    component: $component,
                    operation: $operation,
                    permissions: $options,
                    record: $record
                );
            })
            ->dehydrated(fn ($state): bool => ! blank($state))
            ->gridDirection('row')
            ->columns($columns ?? static::shield()->getCheckboxListColumns())
            ->columnSpan($columnSpan ?? static::shield()->getCheckboxListColumnSpan());
    }

    public static function setPermissionStateForRecordPermissions(Component $component, string $operation, array $permissions, ?Model $record): void
    {
        if (in_array($operation, ['edit', 'view'])) {
            if (blank($record)) {
                return;
            }

            if ($component->isVisible() && count($permissions) > 0) {
                $component->state(
                    collect($permissions)
                        ->filter(function ($value, $key) use ($record) {
                            return static::getPermissions($record)->contains($key);
                        })
                        ->keys()
                        ->toArray()
                );
            }
        }
    }

    public static function getPermissions($record)
    {
        if (! is_null(static::$permissions)) {
            return static::$permissions;
        }

        return static::$permissions = $record->permissions()->pluck('name');
    }

    public static function isProtectedRoleRecord(?Model $record): bool
    {
        return $record instanceof Role && $record->isSystemRole();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function prepareFormDefaults(array $data, ?Role $record = null): array
    {
        $data['starting_point'] ??= 'blank';
        $data['module_access'] ??= [];
        $data['advanced_permissions'] ??= [];
        $data['advanced_editor_open'] ??= false;

        if ($record !== null) {
            $permissions = $record->permissions()->pluck('name');
            $resolver = app(ModulePermissionResolver::class);

            $data['module_access'] = $resolver->inferModuleAccess($permissions);
            $data['advanced_permissions'] = $permissions->all();
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function buildReviewSummaryHtml(array $data): HtmlString
    {
        $summary = app(RolePermissionResolver::class)->buildSummary($data);
        $moduleRows = collect($summary['module_summary'])
            ->filter(fn (array $row): bool => ($row['level'] ?? 'none') !== 'none')
            ->map(fn (array $row): string => sprintf(
                '<tr><td class="px-3 py-2">%s</td><td class="px-3 py-2">%s</td><td class="px-3 py-2">%d / %d</td></tr>',
                e($row['label']),
                e(ModuleAccessLevel::tryFrom($row['level'])?->getLabel() ?? $row['level']),
                $row['selected'],
                $row['total'],
            ))
            ->implode('');

        $permissionsLine = e(__('role-management.summary.permissions', [
            'selected' => $summary['selected_permissions'],
            'total'    => $summary['total_permissions'],
        ]));

        $modulesLine = e(__('role-management.summary.modules', [
            'count' => $summary['modules_with_access'],
            'total' => $summary['total_modules'],
        ]));

        $name = e($data['name'] ?? '');
        $guard = e($data['guard_name'] ?? 'web');

        $table = $moduleRows !== ''
            ? "<table class=\"mt-4 w-full text-sm\"><thead><tr><th class=\"px-3 py-2 text-start\">Module</th><th class=\"px-3 py-2 text-start\">Level</th><th class=\"px-3 py-2 text-start\">Permissions</th></tr></thead><tbody>{$moduleRows}</tbody></table>"
            : '<p class="mt-2 text-sm text-gray-500">'.e(__('role-management.summary.no_modules')).'</p>';

        return new HtmlString(
            '<div class="space-y-2 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                <p class="text-sm"><strong>'.e(__('filament-shield::filament-shield.field.name')).":</strong> {$name}</p>
                <p class=\"text-sm\"><strong>".e(__('filament-shield::filament-shield.field.guard_name')).":</strong> {$guard}</p>
                <p class=\"text-sm\">{$permissionsLine}</p>
                <p class=\"text-sm\">{$modulesLine}</p>
                {$table}
            </div>"
        );
    }
}
