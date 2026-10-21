<?php

return [
    'navigation' => [
        'label'      => 'Branding',
        'title'      => 'Branding',
        'breadcrumb' => 'Branding',
    ],

    'sections' => [
        'identity' => 'System name',
        'logos'    => 'Logos',
    ],

    'form' => [
        'display_name' => [
            'label'  => 'Display name',
            'helper' => 'Shown in the panel, emails, and PDFs. Leave empty to use the active company name.',
        ],
        'logo_full' => [
            'label'  => 'Full logo',
            'helper' => 'Login screen, top bar, module launcher, dashboard, customer panel, and PDFs.',
        ],
        'logo_full_dark' => [
            'label'  => 'Full logo (dark mode)',
            'helper' => 'Optional. The full logo is used when this is empty.',
        ],
        'logo_compact' => [
            'label'  => 'Compact logo',
            'helper' => 'Smaller mark in the sidebar after login. The full logo is used when this is empty.',
        ],
        'logo_compact_dark' => [
            'label'  => 'Compact logo (dark mode)',
            'helper' => 'Optional. The compact logo is used when this is empty.',
        ],
        'favicon' => [
            'label'  => 'Favicon',
            'helper' => 'Browser tab icon. The current favicon is kept when this is empty.',
        ],
    ],
];
