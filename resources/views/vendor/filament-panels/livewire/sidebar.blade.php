<div>
    @php
        use App\Support\Navigation\ModuleSidebarMenuBuilder;
        use App\Support\Navigation\NavigationMode;
        use App\Support\Navigation\SidebarNavigationGroups;

        $navigationMode = NavigationMode::resolve();
        $isRtl = __('filament-panels::layout.direction') === 'rtl';
        $isSidebarCollapsibleOnDesktop = filament()->isSidebarCollapsibleOnDesktop();
        $isSidebarFullyCollapsibleOnDesktop = filament()->isSidebarFullyCollapsibleOnDesktop();
        $isAdminPanel = filament()->getCurrentPanel()->getId() === 'admin';
        $usesModuleSidebar = $isAdminPanel && $navigationMode->usesModuleSidebar();
        $sidebarGroups = $usesModuleSidebar
            ? ModuleSidebarMenuBuilder::build()
            : SidebarNavigationGroups::visibleGroups(filament()->getNavigation(), false)->all();
        $appearanceClass = match ($navigationMode->appearance->value) {
            'light' => 'erp-nav-sidebar--light',
            'dark' => 'erp-nav-sidebar--dark',
            default => 'erp-nav-sidebar--system',
        };
    @endphp

    {{-- format-ignore-start --}}
    <aside
        x-data="{}"
        @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop)
            x-cloak
        @else
            x-cloak="-lg"
        @endif
        x-bind:class="{ 'fi-sidebar-open': $store.sidebar.isOpen }"
        @class([
            'fi-sidebar fi-main-sidebar erp-nav-sidebar',
            $appearanceClass => $usesModuleSidebar,
            'erp-nav-sidebar--module-mode' => $usesModuleSidebar,
        ])
        @foreach ($navigationMode->sidebarDataAttributes() as $attribute => $value)
            {{ $attribute }}="{{ $value }}"
        @endforeach
        @if ($usesModuleSidebar)
            data-erp-default-collapsed-groups="{{ json_encode(
                collect($sidebarGroups)
                    ->filter(fn (\Filament\Navigation\NavigationGroup $group): bool => ! $group->isActive())
                    ->map(fn (\Filament\Navigation\NavigationGroup $group): string => $group->getLabel())
                    ->values()
                    ->all(),
            ) }}"
        @endif
    >
        <div class="fi-sidebar-header-ctn">
            <header class="fi-sidebar-header erp-nav-sidebar__header">
                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_LOGO_BEFORE) }}

                @if ($usesModuleSidebar)
                    @include('filament.components.navigation.sidebar-toggle')
                @endif

	            @if ($homeUrl = filament()->getHomeUrl())
                    <a {{ \Filament\Support\generate_href_html($homeUrl) }} class="erp-nav-sidebar__brand">
                        <x-filament-panels::logo />
                    </a>
                @else
                    <x-filament-panels::logo class="erp-nav-sidebar__brand" />
                @endif

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_LOGO_AFTER) }}
            </header>
        </div>

        @if ($usesModuleSidebar)
            <div class="erp-nav-sidebar__tools">
                <div
                    x-cloak
                    x-show="$store.sidebar.isOpen"
                    class="erp-nav-sidebar-filter"
                >
                    <x-filament::icon
                        icon="heroicon-o-magnifying-glass"
                        class="erp-nav-sidebar-filter__icon"
                    />

                    <input
                        x-ref="menuFilter"
                        x-bind:value="$store.erpNavigationFilter.query"
                        x-on:input.debounce.150ms="$store.erpNavigationFilter.setQuery($event.target.value)"
                        x-on:keydown.escape.prevent="$store.erpNavigationFilter.clear(); $refs.menuFilter.blur()"
                        type="search"
                        autocomplete="off"
                        aria-label="{{ __('navigation-settings.sidebar.filter_label') }}"
                        placeholder="{{ __('navigation-settings.sidebar.filter_placeholder') }}"
                        class="erp-nav-sidebar-filter__input"
                    />

                    <button
                        x-cloak
                        x-show="$store.erpNavigationFilter.query.length > 0"
                        x-on:click="$store.erpNavigationFilter.clear(); $refs.menuFilter.focus()"
                        type="button"
                        aria-label="{{ __('navigation-settings.sidebar.clear_filter') }}"
                        class="erp-nav-sidebar-filter__clear"
                    >
                        <x-filament::icon icon="heroicon-o-x-mark" />
                    </button>
                </div>

                @include('filament.components.navigation.sidebar-toggle')
            </div>
        @endif

        <nav class="fi-sidebar-nav erp-nav-sidebar__nav">
            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_NAV_START) }}

            @if (filament()->hasTenancy() && filament()->hasTenantMenu())
                <div class="fi-sidebar-nav-tenant-menu-ctn">
                    <x-filament-panels::tenant-menu />
                </div>
            @endif

            <ul class="fi-sidebar-nav-groups erp-nav-sidebar__groups">
                @foreach ($sidebarGroups as $group)
                    @php
                        $isGroupActive = $group->isActive();
                        $isGroupCollapsible = $usesModuleSidebar ? true : $group->isCollapsible();
                        $groupIcon = $group->getIcon();
                        $groupItems = $group->getItems();
                        $groupLabel = $group->getLabel();
                        $groupExtraSidebarAttributeBag = $group->getExtraSidebarAttributeBag();
                    @endphp

                    @if ($usesModuleSidebar)
                        <x-filament-panels::sidebar.group
                            :active="$isGroupActive"
                            :collapsible="$isGroupCollapsible"
                            :icon="$groupIcon"
                            :items="$groupItems"
                            :label="$groupLabel"
                            :attributes="\Filament\Support\prepare_inherited_attributes($groupExtraSidebarAttributeBag)"
                        />
                    @else
                        <ul class="fi-sidebar-group-items">
                            @foreach ($groupItems as $item)
                                @php
                                    $isItemActive = $item->isActive();
                                    $isItemChildItemsActive = $item->isChildItemsActive();
                                    $itemActiveIcon = $item->getActiveIcon();
                                    $itemBadge = $item->getBadge();
                                    $itemBadgeColor = $item->getBadgeColor();
                                    $itemBadgeTooltip = $item->getBadgeTooltip();
                                    $itemChildItems = $item->getChildItems();
                                    $itemIcon = $item->getIcon();
                                    $shouldItemOpenUrlInNewTab = $item->shouldOpenUrlInNewTab();
                                    $itemUrl = $item->getUrl();
                                @endphp

                                <x-filament-panels::sidebar.item
                                    :active="$isItemActive"
                                    :active-child-items="$isItemChildItemsActive"
                                    :active-icon="$itemActiveIcon"
                                    :badge="$itemBadge"
                                    :badge-color="$itemBadgeColor"
                                    :badge-tooltip="$itemBadgeTooltip"
                                    :child-items="$itemChildItems"
                                    :first="$loop->first"
                                    :grouped="false"
                                    :icon="$itemIcon"
                                    :last="$loop->last"
                                    :should-open-url-in-new-tab="$shouldItemOpenUrlInNewTab"
                                    sidebar-collapsible="true"
                                    :url="$itemUrl"
                                >
                                    {{ $item->getLabel() }}

                                    @if ($itemIcon instanceof \Illuminate\Contracts\Support\Htmlable)
                                        <x-slot name="icon">
                                            {{ $itemIcon }}
                                        </x-slot>
                                    @endif

                                    @if ($itemActiveIcon instanceof \Illuminate\Contracts\Support\Htmlable)
                                        <x-slot name="activeIcon">
                                            {{ $itemActiveIcon }}
                                        </x-slot>
                                    @endif
                                </x-filament-panels::sidebar.item>
                            @endforeach
                        </ul>
                    @endif
                @endforeach
            </ul>

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_NAV_END) }}
        </nav>

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_FOOTER) }}
    </aside>
    {{-- format-ignore-end --}}

    <x-filament-actions::modals />
</div>
