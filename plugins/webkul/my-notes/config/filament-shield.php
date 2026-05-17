<?php

use Webkul\MyNotes\Filament\Pages\MyNotesPage;
use Webkul\MyNotes\Filament\Widgets\UpcomingRemindersWidget;

return [
    'resources' => [
        'manage'  => [],
        'exclude' => [],
    ],
    'pages' => [
        'exclude' => [
            MyNotesPage::class,
        ],
    ],
    'widgets' => [
        'exclude' => [
            UpcomingRemindersWidget::class,
        ],
    ],
];
