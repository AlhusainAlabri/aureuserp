<?php

namespace App\Support\ModuleLauncher;

use App\Models\DashboardShortcut;
use App\Support\AdminNavigationMenu;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ModuleLauncherItems
{
    /**
     * @var array<string, string>
     */
    private const ICON_COLORS = [
        'icon-dashboard'        => 'primary',
        'icon-contacts'         => 'info',
        'icon-sales'            => 'info',
        'icon-purchases'        => 'success',
        'icon-manufacturing'    => 'warning',
        'icon-inventories'      => 'warning',
        'icon-invoices'         => 'success',
        'icon-accounting'       => 'success',
        'icon-projects'         => 'info',
        'icon-meetings'         => 'danger',
        'icon-my-notes'         => 'warning',
        'icon-correspondence'   => 'danger',
        'icon-document-archive' => 'info',
        'icon-assets'           => 'warning',
        'icon-employees'        => 'danger',
        'icon-payroll'          => 'success',
        'icon-time-offs'        => 'info',
        'icon-recruitments'     => 'info',
        'icon-website'          => 'info',
        'icon-approvals'        => 'warning',
        'icon-plugin'           => 'gray',
        'icon-settings'         => 'gray',
    ];

    /**
     * @return Collection<int, array{key: string, label: string, icon: string, url: string, color: string, type: string, opensInNewTab: bool}>
     */
    public static function forCurrentUser(): Collection
    {
        return self::visibleForCurrentUser();
    }

    /**
     * @return Collection<int, array{key: string, label: string, icon: string, url: string, color: string, type: string, opensInNewTab: bool}>
     */
    public static function allForCurrentUser(): Collection
    {
        return self::buildItemsForCurrentUser();
    }

    /**
     * @return Collection<int, array{key: string, label: string, icon: string, url: string, color: string, type: string, opensInNewTab: bool}>
     */
    public static function visibleForCurrentUser(): Collection
    {
        $items = self::buildItemsForCurrentUser();
        $hidden = array_flip(ModuleLauncherPreferenceStore::hiddenKeys());

        return $items
            ->filter(fn (array $item): bool => ! array_key_exists($item['key'], $hidden))
            ->values();
    }

    /**
     * @return array<string, string>
     */
    public static function customizeModuleOptions(): array
    {
        return self::allForCurrentUser()
            ->where('type', 'module')
            ->mapWithKeys(fn (array $item): array => [$item['key'] => $item['label']])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function customizeShortcutOptions(): array
    {
        return self::allForCurrentUser()
            ->where('type', 'shortcut')
            ->mapWithKeys(fn (array $item): array => [$item['key'] => $item['label']])
            ->all();
    }

    /**
     * @return Collection<int, array{key: string, label: string, icon: string, url: string, color: string, type: string, opensInNewTab: bool}>
     */
    protected static function buildItemsForCurrentUser(): Collection
    {
        $panel = Filament::getCurrentPanel();

        if ($panel === null) {
            return collect();
        }

        $modules = AdminNavigationMenu::primaryMenuItems(
            Filament::getNavigation(),
            $panel->getNavigationGroups(),
        )->map(fn (array $item): array => [
            'key'            => self::moduleKey($item['icon']),
            'label'          => $item['label'],
            'icon'           => $item['icon'],
            'url'            => $item['url'],
            'color'          => self::colorForIcon($item['icon']),
            'type'           => 'module',
            'opensInNewTab'  => false,
        ]);

        if (! Schema::hasTable('dashboard_shortcuts')) {
            return $modules->values();
        }

        $shortcuts = DashboardShortcut::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->orderBy('title_en')
            ->get()
            ->map(fn (DashboardShortcut $shortcut): array => [
                'key'            => self::shortcutKey($shortcut->id),
                'label'          => $shortcut->getTitle(),
                'icon'           => $shortcut->icon,
                'url'            => $shortcut->getResolvedUrl(),
                'color'          => $shortcut->color,
                'type'           => 'shortcut',
                'opensInNewTab'  => $shortcut->opens_in_new_tab,
            ]);

        return $modules->concat($shortcuts)->values();
    }

    public static function moduleKey(string $icon): string
    {
        return 'module:'.$icon;
    }

    public static function shortcutKey(int $shortcutId): string
    {
        return 'shortcut:'.$shortcutId;
    }

    public static function colorForIcon(string $icon): string
    {
        return self::ICON_COLORS[$icon] ?? 'gray';
    }

    /**
     * @return array<string, string>
     */
    public static function availableColors(): array
    {
        return [
            'primary' => __('module-launcher.colors.primary'),
            'info'    => __('module-launcher.colors.info'),
            'success' => __('module-launcher.colors.success'),
            'warning' => __('module-launcher.colors.warning'),
            'danger'  => __('module-launcher.colors.danger'),
            'gray'    => __('module-launcher.colors.gray'),
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function shortcutIconOptions(): array
    {
        return [
            __('module-launcher.shortcuts.icons.groups.modules') => self::moduleIconOptions(),
            __('module-launcher.shortcuts.icons.groups.general') => self::generalIconOptions(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function shortcutIconOptionsFlat(): array
    {
        return array_merge(self::moduleIconOptions(), self::generalIconOptions());
    }

    /**
     * @return array<string, string>
     */
    public static function moduleIconOptions(): array
    {
        return [
            'icon-dashboard'        => __('admin.navigation.dashboard'),
            'icon-contacts'         => __('admin.navigation.contact'),
            'icon-sales'            => __('admin.navigation.sale'),
            'icon-purchases'        => __('admin.navigation.purchase'),
            'icon-manufacturing'    => __('admin.navigation.manufacturing'),
            'icon-inventories'      => __('admin.navigation.inventory'),
            'icon-invoices'         => __('admin.navigation.invoice'),
            'icon-accounting'       => __('admin.navigation.accounting'),
            'icon-projects'         => __('admin.navigation.project'),
            'icon-meetings'         => __('admin.navigation.meetings'),
            'icon-my-notes'         => __('admin.navigation.my-notes'),
            'icon-correspondence'   => __('admin.navigation.correspondence'),
            'icon-document-archive' => __('admin.navigation.document-archive'),
            'icon-assets'           => __('assets::assets.navigation.group'),
            'icon-employees'        => __('admin.navigation.employee'),
            'icon-payroll'          => __('payroll::payroll.navigation.group'),
            'icon-time-offs'        => __('admin.navigation.time-off'),
            'icon-recruitments'     => __('admin.navigation.recruitment'),
            'icon-website'          => __('admin.navigation.website'),
            'icon-approvals'        => __('admin.navigation.approvals'),
            'icon-plugin'           => __('admin.navigation.plugin'),
            'icon-settings'         => __('admin.navigation.setting'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function generalIconOptions(): array
    {
        return [
            'heroicon-o-link'                      => __('module-launcher.shortcuts.icons.general.link'),
            'heroicon-o-arrow-top-right-on-square' => __('module-launcher.shortcuts.icons.general.external_link'),
            'heroicon-o-globe-alt'                 => __('module-launcher.shortcuts.icons.general.website'),
            'heroicon-o-document-text'             => __('module-launcher.shortcuts.icons.general.document'),
            'heroicon-o-folder'                    => __('module-launcher.shortcuts.icons.general.folder'),
            'heroicon-o-calendar-days'             => __('module-launcher.shortcuts.icons.general.calendar'),
            'heroicon-o-envelope'                  => __('module-launcher.shortcuts.icons.general.email'),
            'heroicon-o-phone'                     => __('module-launcher.shortcuts.icons.general.phone'),
            'heroicon-o-map-pin'                   => __('module-launcher.shortcuts.icons.general.location'),
            'heroicon-o-building-office-2'         => __('module-launcher.shortcuts.icons.general.building'),
            'heroicon-o-user-group'                => __('module-launcher.shortcuts.icons.general.people'),
            'heroicon-o-chart-bar'                 => __('module-launcher.shortcuts.icons.general.chart'),
            'heroicon-o-cog-6-tooth'               => __('module-launcher.shortcuts.icons.general.settings'),
            'heroicon-o-shield-check'              => __('module-launcher.shortcuts.icons.general.security'),
            'heroicon-o-book-open'                 => __('module-launcher.shortcuts.icons.general.guide'),
            'heroicon-o-question-mark-circle'      => __('module-launcher.shortcuts.icons.general.help'),
            'heroicon-o-bell'                      => __('module-launcher.shortcuts.icons.general.notifications'),
            'heroicon-o-chat-bubble-left-right'    => __('module-launcher.shortcuts.icons.general.chat'),
            'heroicon-o-cloud-arrow-down'          => __('module-launcher.shortcuts.icons.general.download'),
            'heroicon-o-video-camera'              => __('module-launcher.shortcuts.icons.general.video'),
        ];
    }
}
