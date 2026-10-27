<?php

namespace App\Support\Navigation;

use App\Enums\NavigationAppearance;
use App\Enums\NavigationMenuType;
use App\Enums\SidebarDefaultState;
use App\Settings\NavigationSettings;
use App\Support\SchemaMemo;

class NavigationMode
{
    public function __construct(
        public readonly NavigationMenuType $menuType,
        public readonly SidebarDefaultState $sidebarDefaultState,
        public readonly NavigationAppearance $appearance,
    ) {}

    public static function resolve(): self
    {
        try {
            if (! SchemaMemo::hasTable('settings')) {
                return self::defaults();
            }

            $settings = app(NavigationSettings::class);

            return new self(
                $settings->menu_type,
                $settings->sidebar_default_state,
                $settings->menu_appearance,
            );
        } catch (\Throwable) {
            return self::defaults();
        }
    }

    public static function defaults(): self
    {
        return new self(
            NavigationMenuType::AppGrid,
            SidebarDefaultState::Auto,
            NavigationAppearance::System,
        );
    }

    public function usesTopNavigation(): bool
    {
        return in_array($this->menuType, [
            NavigationMenuType::TopNavigation,
            NavigationMenuType::AppGrid,
        ], true);
    }

    public function usesAppGrid(): bool
    {
        return $this->menuType === NavigationMenuType::AppGrid;
    }

    public function usesModuleSidebar(): bool
    {
        return $this->menuType === NavigationMenuType::Sidebar;
    }

    /**
     * @return array<string, string>
     */
    public function sidebarDataAttributes(): array
    {
        return [
            'data-nav-mode'       => $this->menuType->value,
            'data-nav-appearance' => $this->appearance->value,
            'data-sidebar-default'=> $this->sidebarDefaultState->value,
        ];
    }
}
