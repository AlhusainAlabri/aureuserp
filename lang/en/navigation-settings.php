<?php

return [
    'navigation' => [
        'group'      => 'General',
        'label'      => 'Navigation / Menu',
        'title'      => 'Navigation / Menu Settings',
        'breadcrumb' => 'Navigation / Menu Settings',
    ],

    'sections' => [
        'menu'       => 'Menu Type',
        'sidebar'    => 'Sidebar Defaults',
        'appearance' => 'Appearance',
    ],

    'form' => [
        'menu_type' => [
            'label'  => 'Menu type',
            'helper' => 'Choose how the main application navigation is displayed for all users.',
        ],
        'sidebar_default_state' => [
            'label'  => 'Sidebar default state',
            'helper' => 'Default width when users first open the sidebar. Users can still collapse or expand it; their preference is remembered.',
        ],
        'menu_appearance' => [
            'label'  => 'Menu appearance',
            'helper' => 'Sidebar colour scheme. “Follow system” uses the current Filament light/dark theme.',
        ],
    ],

    'menu_type' => [
        'sidebar'         => 'Sidebar',
        'top_navigation'  => 'Top navigation',
        'app_grid'        => 'App grid / current menu',
    ],

    'sidebar_default_state' => [
        'expanded'  => 'Expanded by default',
        'collapsed' => 'Collapsed by default',
        'auto'      => 'Auto (expanded on desktop, compact on tablet)',
    ],

    'menu_appearance' => [
        'light'  => 'Light',
        'dark'   => 'Dark',
        'system' => 'Follow system / app theme',
    ],

    'sidebar' => [
        'personal'           => 'My workspace',
        'filter_label'       => 'Filter navigation menu',
        'filter_placeholder' => 'Filter menu…',
        'clear_filter'       => 'Clear menu filter',
    ],
];
