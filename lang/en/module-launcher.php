<?php

return [
    'navigation' => [
        'title' => 'Main Dashboard',
    ],

    'greeting'   => 'Welcome back, :name',
    'subheading' => 'Access all ERP modules from one place.',

    'search' => [
        'label'       => 'Search',
        'placeholder' => 'Search across all modules…',
    ],

    'opens_in_new_tab' => 'Opens in a new tab',

    'empty' => [
        'title'       => 'No modules available',
        'description' => 'You do not have access to any modules yet. Contact your administrator.',
    ],

    'customize' => [
        'action'                => 'Customize',
        'heading'               => 'Customize your dashboard',
        'description'           => 'Choose which modules and shortcuts appear on your main dashboard. Your layout is saved automatically for your account.',
        'section'               => 'Visible items',
        'section_help'          => 'Uncheck any item to hide it from your dashboard. You can restore hidden items at any time.',
        'visible_items'         => 'Show on dashboard',
        'save'                  => 'Save layout',
        'saved'                 => 'Dashboard layout saved',
        'reset'                 => 'Show all',
        'reset_done'            => 'All modules and shortcuts are visible again',
        'all_visible'           => 'All modules and shortcuts are visible',
        'hidden_count'          => '{1} :count hidden item|[2,*] :count hidden items',
        'all_hidden_title'      => 'Everything is hidden',
        'all_hidden_description'=> 'Turn items back on using Customize to restore modules and shortcuts on your dashboard.',
        'groups'                => [
            'modules'   => 'Modules',
            'shortcuts' => 'Shortcuts',
        ],
    ],

    'colors' => [
        'primary' => 'Green',
        'info'    => 'Blue',
        'success' => 'Emerald',
        'warning' => 'Orange',
        'danger'  => 'Rose',
        'gray'    => 'Gray',
    ],

    'shortcuts' => [
        'navigation' => [
            'title' => 'Dashboard Shortcuts',
        ],
        'model'   => 'Shortcut',
        'plural'  => 'Dashboard Shortcuts',
        'form'    => [
            'section'            => 'Shortcut details',
            'title_en'           => 'Title (English)',
            'title_ar'           => 'Title (Arabic)',
            'url'                => 'URL',
            'url_help'           => 'Use a full URL (https://…) or an internal path (/admin/…).',
            'icon'               => 'Icon',
            'icon_help'          => 'Search and pick an icon from the list.',
            'icon_preview'       => 'Preview',
            'icon_preview_empty' => 'Select an icon to see a preview.',
            'color'              => 'Accent color',
            'sort'               => 'Sort order',
            'is_active'          => 'Active',
            'opens_in_new_tab'   => 'Open in new tab',
        ],
        'icons' => [
            'groups' => [
                'modules' => 'ERP modules',
                'general' => 'General icons',
            ],
            'general' => [
                'link'     => 'Link', 'external_link' => 'External link', 'website' => 'Website',
                'document' => 'Document', 'folder' => 'Folder', 'calendar' => 'Calendar',
                'email'    => 'Email', 'phone' => 'Phone', 'location' => 'Location',
                'building' => 'Building', 'people' => 'People', 'chart' => 'Chart',
                'settings' => 'Settings', 'security' => 'Security', 'guide' => 'Guide',
                'help'     => 'Help', 'notifications' => 'Notifications', 'chat' => 'Chat',
                'download' => 'Download', 'video' => 'Video',
            ],
        ],
        'table' => [
            'title_en'  => 'Title (EN)',
            'title_ar'  => 'Title (AR)',
            'url'       => 'URL',
            'icon'      => 'Icon',
            'sort'      => 'Sort',
            'is_active' => 'Active',
        ],
    ],
];
